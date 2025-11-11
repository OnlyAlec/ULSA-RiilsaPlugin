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
    private EmailCampaignsApi $apiEmailCampaigns;
    private ListsApi $apiLists;
    private SendersApi $apiSenders;
    private TransactionalEmailsApi $apiTransactionalEmails;
    private ContactsApi $apiContacts;
    
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
        $this->loadSender();
        $this->ensureContactLists();
    }
    
    /**
     * Initialize Brevo API clients
     *
     * @return void
     * @throws \RuntimeException
     */
    private function initializeBrevoApis(): void
    {
        // Load environment variables
        $dotenv = \Dotenv\Dotenv::createImmutable(RIILSA_PLUGIN_DIR);
        $dotenv->safeLoad();
        
        $apiKey = $_ENV['API_KEY'] ?? null;
        
        if (!$apiKey) {
            throw new \RuntimeException('Brevo API key not configured');
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
    }
    
    /**
     * Load active sender
     *
     * @return void
     * @throws \RuntimeException
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
            
            throw new \RuntimeException('Brevo sender not found: ' . RIILSA_MAIL_SENDER_NAME);
            
        } catch (\Exception $e) {
            debugLog('Failed to load Brevo sender: ' . $e->getMessage(), 'error');
            throw new \RuntimeException('Failed to load Brevo sender: ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure contact lists exist
     *
     * @return void
     */
    private function ensureContactLists(): void
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
                    if (!in_array($dependency['description'], $existingNames)) {
                        $this->createContactList($dependency['description']);
                    }
                }
            }
            
        } catch (\Exception $e) {
            debugLog('Failed to ensure contact lists: ' . $e->getMessage(), 'warning');
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
            
            return (string)$contact->getId();
            
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
        try {
            // Map dependency IDs to Brevo list IDs
            $brevoListIds = $this->mapDependencyIdsToListIds($listIds);
            
            $campaignData = [
                'tag' => $tag,
                'subject' => $subject,
                'previewText' => $subject,
                'htmlContent' => $html,
                'sender' => new CreateEmailCampaignSender(['id' => $this->sender['id']]),
                'recipients' => new CreateEmailCampaignRecipients(['listIds' => $brevoListIds]),
            ];
            
            if ($scheduledAt) {
                $campaignData['scheduledAt'] = $scheduledAt->format(\DateTime::ATOM);
            }
            
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
