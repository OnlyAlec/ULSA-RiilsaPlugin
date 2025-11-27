<?php

declare(strict_types=1);

/**
 * Brevo Mail Service Implementation
 *
 * @package RIILSA\Infrastructure\Services
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Services;

use GuzzleHttp\Client;
use Brevo\Client\Configuration;
use Brevo\Client\Api\EmailCampaignsApi;
use Brevo\Client\Api\ListsApi;
use Brevo\Client\Api\SendersApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Model\CreateEmailCampaign;
use Brevo\Client\Model\CreateEmailCampaignSender;
use Brevo\Client\Model\CreateEmailCampaignRecipients;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailSender;
use Brevo\Client\Model\SendSmtpEmailTo;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\UpdateContact;
use Brevo\Client\Model\CreateList;

use function RIILSA\Core\debugLog;

/**
 * Brevo email service implementation
 *
 * Pattern: Service Pattern
 * This class provides email sending capabilities using the Brevo API
 */
class BrevoMailService
{
    /**
     * API instances
     */
    private ?EmailCampaignsApi $apiEmailCampaigns = null;
    private ?ListsApi $apiLists = null;
    private ?SendersApi $apiSenders = null;
    private ?TransactionalEmailsApi $apiTransactionalEmails = null;
    private ?ContactsApi $apiContacts = null;

    /**
     * Service configuration status
     */
    private bool $isConfigured = false;

    /**
     * Active sender
     *
     * @var array|null
     */
    private ?array $sender = null;

    /**
     * Contact lists cache
     *
     * @var array|null
     */
    private ?array $contactLists = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initializeBrevoApis();

        if ($this->isConfigured) {
            $this->loadSender();
            $this->ensureContactLists();
        }
    }

    /**
     * Initialize Brevo API clients
     *
     * @return void
     */
    private function initializeBrevoApis(): void
    {
        // Load environment variables
        $dotenv = \Dotenv\Dotenv::createImmutable(RIILSA_PLUGIN_DIR);
        $dotenv->safeLoad();

        $apiKey = $_ENV['API_KEY'] ?? null;

        if (!$apiKey) {
            debugLog('Brevo API key not configured', 'warning');
            return;
        }

        // Configure Brevo
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $client = new Client();

        // Initialize API instances
        $this->apiEmailCampaigns = new EmailCampaignsApi($client, $config);
        $this->apiLists = new ListsApi($client, $config);
        $this->apiContacts = new ContactsApi($client, $config);
        $this->apiTransactionalEmails = new TransactionalEmailsApi($client, $config);
        $this->apiSenders = new SendersApi($client, $config);

        $this->isConfigured = true;
    }

    /**
     * Load active sender
     *
     * @return void
     */
    private function loadSender(): void
    {
        try {
            $senders = $this->apiSenders->getSenders();

            foreach ($senders->getSenders() as $sender) {
                if ($sender->getName() === RIILSA_MAIL_SENDER_NAME) {
                    $this->sender = [
                        'id' => $sender->getId(),
                        'name' => $sender->getName(),
                        'email' => $sender->getEmail(),
                    ];
                    return;
                }
            }

            debugLog('Brevo sender not found: ' . RIILSA_MAIL_SENDER_NAME, 'error');
            $this->isConfigured = false;

        } catch (\Exception $e) {
            debugLog('Failed to load Brevo sender: ' . $e->getMessage(), 'error');
            $this->isConfigured = false;
        }
    }

    /**
     * Ensure contact lists exist
     *
     * @return void
     */
    private function ensureContactLists($skipId = -1)
    {
        try {
            $this->contactLists = $this->apiLists->getLists(RIILSA_MAIL_LIST_LIMIT, 0, "desc")->getLists();

            // Get dependencies from database
            global $wpdb;
            $dependencies = $wpdb->get_results(
                "SELECT * FROM " . RIILSA_TABLE_DEPENDENCY_CATALOG,
                ARRAY_A
            );

            if (empty($this->contactLists)) {
                // Create lists for all dependencies
                foreach ($dependencies as $dependency) {
                    $this->createContactList($dependency['description']);
                }
            } else {
                // Check for missing lists
                $existingNames = array_column($this->contactLists, 'name');

                foreach ($dependencies as $dependency) {
                    if ($skipId !== $dependency['id']) {
                        if (!in_array($dependency['description'], $existingNames)) {
                            $this->createContactList($dependency['description']);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            debugLog('Failed to ensure contact lists: ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * Add contacts to list
     *
     * @param int $listId
     * @param array $emails
     * @return void
     * @throws \RuntimeException
     */
    public function addContactsToList(int $listId, array $emails): void
    {
        if (!$this->isConfigured || empty($emails)) {
            return;
        }

        try {
            // Brevo API limits adding contacts in batches (usually 150 or similar, check docs)
            // Safe batch size
            $batchSize = 100;
            $chunks = array_chunk($emails, $batchSize);

            foreach ($chunks as $chunk) {
                $contactEmails = new \Brevo\Client\Model\AddContactToList(['emails' => $chunk]);
                $this->apiLists->addContactToList($listId, $contactEmails);
            }

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to add contacts to list: ' . $e->getMessage());
        }
    }

    /**
     * Create contact list
     *
     * @param string $name
     * @return array
     * @throws \RuntimeException
     */
    public function createContactList(string $name): array
    {
        if (!$this->isConfigured) {
            return ['id' => 0, 'name' => $name];
        }

        try {
            $newList = new CreateList([
                'name' => $name,
                'folderId' => 1
            ]);

            $list = $this->apiLists->createList($newList);

            // Refresh contact lists cache
            $this->contactLists = null;
            $this->ensureContactLists();

            return [
                'id' => $list->getId(),
                'name' => $name,
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create contact list: ' . $e->getMessage());
        }
    }

    /**
     * Create contact
     *
     * @param string $email
     * @param int $dependencyId
     * @return string External contact ID
     * @throws \RuntimeException
     */
    public function createContact(string $email, int $dependencyId): string
    {
        if (!$this->isConfigured) {
            return '0';
        }

        try {
            $listId = $this->getListIdByDependencyId($dependencyId);

            if (!$listId) {
                throw new \RuntimeException('List not found for dependency ID: ' . $dependencyId);
            }

            $newContact = new CreateContact([
                'email' => $email,
                'listIds' => [$listId],
                'emailBlacklisted' => true, // Pending confirmation
            ]);

            $contact = $this->apiContacts->createContact($newContact);

            return (string) $contact->getId();

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create contact: ' . $e->getMessage());
        }
    }

    /**
     * Confirm contact (remove from blacklist)
     *
     * @param string $email
     * @return void
     * @throws \RuntimeException
     */
    public function confirmContact(string $email): void
    {
        if (!$this->isConfigured) {
            return;
        }

        try {
            $contact = $this->apiContacts->getContactInfo($email);

            $updateData = new UpdateContact(['emailBlacklisted' => false]);

            $this->apiContacts->updateContact($contact->getId(), $updateData);

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to confirm contact: ' . $e->getMessage());
        }
    }

    /**
     * Unsubscribe contact (add to blacklist)
     *
     * @param string $email
     * @return void
     * @throws \RuntimeException
     */
    public function unsubscribeContact(string $email): void
    {
        if (!$this->isConfigured) {
            return;
        }

        try {
            $contact = $this->apiContacts->getContactInfo($email);

            $updateData = new UpdateContact(['emailBlacklisted' => true]);

            $this->apiContacts->updateContact($contact->getId(), $updateData);

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to unsubscribe contact: ' . $e->getMessage());
        }
    }

    /**
     * Delete contact
     *
     * @param string $email
     * @return void
     * @throws \RuntimeException
     */
    public function deleteContact(string $email): void
    {
        if (!$this->isConfigured) {
            return;
        }

        try {
            $this->apiContacts->deleteContact($email);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to delete contact: ' . $e->getMessage());
        }
    }

    /**
     * Send transactional email
     *
     * @param string $email
     * @param array $data Must contain 'subject' and 'html'
     * @return void
     * @throws \RuntimeException
     */
    public function sendTransactionalEmail(string $email, array $data): void
    {
        if (!$this->isConfigured) {
            return;
        }

        try {
            $sendSmtpEmail = new SendSmtpEmail([
                'sender' => new SendSmtpEmailSender(['id' => $this->sender['id']]),
                'to' => [new SendSmtpEmailTo(['email' => $email])],
                'htmlContent' => $data['html'],
                'subject' => $data['subject'],
                'tags' => $data['tags'] ?? ['RIILSA_TRANSACTIONAL'],
            ]);

            $this->apiTransactionalEmails->sendTransacEmail($sendSmtpEmail);

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to send transactional email: ' . $e->getMessage());
        }
    }

    /**
     * Create and send email campaign
     *
     * @param array $listIds List IDs to send to
     * @param string $html HTML content
     * @param string $tag Campaign tag
     * @param string $subject Email subject
     * @param \DateTimeInterface|null $scheduledAt Optional scheduled date
     * @return array Result with success status and campaign ID
     * @throws \RuntimeException
     */
    public function createAndSendCampaign(
        array $listIds,
        string $html,
        string $tag,
        string $subject,
        ?\DateTimeInterface $scheduledAt = null
    ): array {
        if (!$this->isConfigured) {
            return [
                'success' => false,
                'error' => 'Brevo service not configured',
            ];
        }

        try {
            $brevoListIds = $this->mapDependencyIdsToListIds($listIds);

            $campaignData = [
                'name' => $subject,
                'tag' => $tag,
                'subject' => $subject,
                'previewText' => $subject,
                'htmlContent' => $html,
                'sender' => new CreateEmailCampaignSender(['id' => $this->sender['id']]),
                'recipients' => new CreateEmailCampaignRecipients(['listIds' => $brevoListIds]),
            ];

            // Use Mexico City timezone for scheduled date
            $tz = new \DateTimeZone('America/Mexico_City');

            if ($scheduledAt instanceof \DateTimeInterface) {
                if ($scheduledAt instanceof \DateTimeImmutable) {
                    $scheduledAtLocal = $scheduledAt->setTimezone($tz);
                } else {
                    $tmp = clone $scheduledAt;
                    $tmp->setTimezone($tz);
                    $scheduledAtLocal = $tmp;
                }
            } else {
                $scheduledAtLocal = (new \DateTimeImmutable('now', $tz))->modify('+2 minutes');
            }

            $format = (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70200) ? 'Y-m-d\TH:i:s.vP' : 'Y-m-d\TH:i:sP';
            $campaignData['scheduledAt'] = $scheduledAtLocal->format($format);

            $createEmailCampaign = new CreateEmailCampaign($campaignData);
            $campaign = $this->apiEmailCampaigns->createEmailCampaign($createEmailCampaign);

            return [
                'success' => true,
                'campaignId' => $campaign->getId(),
            ];

        } catch (\Exception $e) {
            debugLog('Failed to create email campaign: ' . $e->getMessage(), 'error');

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Brevo service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Delete contact list
     *
     * @param int $listId
     * @return void
     * @throws \RuntimeException
     */
    public function deleteContactList(int $listId): void
    {
        if (!$this->isConfigured) {
            return;
        }

        try {
            $this->apiLists->deleteList($listId);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to delete contact list: ' . $e->getMessage());
        }
    }

    /**
     * Delete list by dependency ID
     *
     * @param int $dependencyId
     * @return void
     * @throws \RuntimeException
     */
    public function deleteListByDependencyId(int $dependencyId): void
    {
        $listId = $this->getListIdByDependencyId($dependencyId);

        if ($listId) {
            $this->deleteContactList($listId);
        }
    }

    /**
     * Get list ID by dependency ID
     *
     * @param int $dependencyId
     * @return int|null
     */
    private function getListIdByDependencyId(int $dependencyId): ?int
    {
        global $wpdb;

        $dependency = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . RIILSA_TABLE_DEPENDENCY_CATALOG . " WHERE id = %d",
            $dependencyId
        ), ARRAY_A);

        if (!$dependency) {
            return null;
        }

        // Find corresponding list in Brevo
        foreach ($this->contactLists ?? [] as $list) {
            if ($list['name'] === $dependency['description']) {
                return $list['id'];
            }
        }

        return null;
    }

    /**
     * Map dependency IDs to Brevo list IDs
     *
     * @param array $dependencyIds
     * @return array
     */
    private function mapDependencyIdsToListIds(array $dependencyIds): array
    {
        $listIds = [];

        foreach ($dependencyIds as $dependencyId) {
            $listId = $this->getListIdByDependencyId($dependencyId);
            if ($listId) {
                $listIds[] = $listId;
            }
        }

        return $listIds;
    }

    /**
     * Get contact lists
     *
     * @return array
     */
    public function getContactLists(): array
    {
        if ($this->contactLists === null) {
            $this->ensureContactLists();
        }

        return $this->contactLists ?? [];
    }

    /**
     * Get sender information
     *
     * @return array
     */
    public function getSender(): array
    {
        return $this->sender;
    }
}
