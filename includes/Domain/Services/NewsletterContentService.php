<?php

declare(strict_types=1);

/**
 * Newsletter Content Domain Service
 *
 * @package RIILSA\Domain\Services
 * @since 3.1.0
 */

namespace RIILSA\Domain\Services;

use RIILSA\Domain\Entities\News;
use RIILSA\Domain\Entities\Newsletter;

/**
 * Domain service for newsletter content management
 *
 * Pattern: Domain Service Pattern
 * This service handles the business logic for organizing
 * and validating newsletter content
 */
class NewsletterContentService
{
    /**
     * Maximum news items per category
     *
     * @var array
     */
    private array $categoryLimits = [
        'highlight' => 3,
        'normal' => 9,
        'grid' => 9
    ];

    /**
     * Validate and categorize news items for a newsletter
     *
     * @param array<News> $newsItems
     * @param array<int> $selectedIds The IDs selected by the user
     * @return array Categorized news items
     * @throws \DomainException If validation fails
     */
    public function categorizeNewsItems(array $newsItems, array $selectedIds): array
    {
        // Validate all news items are published
        foreach ($newsItems as $news) {
            if (!$news->isPublished()) {
                throw new \DomainException(
                    "News item '{$news->getTitle()}' is not published and cannot be added to newsletter"
                );
            }
        }

        // Sort news items by the order of selected IDs
        $sortedNews = $this->sortNewsBySelection($newsItems, $selectedIds);

        // Categorize based on position
        $categorized = [
            'highlight' => [],
            'normal' => [],
            'grid' => []
        ];

        foreach ($sortedNews as $news) {
            $position = $news->getPosition();

            if (!isset($categorized[$position])) {
                $position = 'normal'; // Default position
            }

            // Check category limits
            if (count($categorized[$position]) >= $this->categoryLimits[$position]) {
                // Try to place in another category
                $position = $this->findAlternativePosition($categorized, $position);
                if (!$position) {
                    throw new \DomainException(
                        "Cannot add more news items. All categories are at their limits."
                    );
                }
            }

            $categorized[$position][] = $news;
        }

        return $categorized;
    }

    /**
     * Sort news items by the order of selected IDs
     *
     * @param array<News> $newsItems
     * @param array<int> $selectedIds
     * @return array<News>
     */
    private function sortNewsBySelection(array $newsItems, array $selectedIds): array
    {
        // Create a map of ID to news item
        $newsMap = [];
        foreach ($newsItems as $news) {
            $newsMap[$news->getId()] = $news;
        }

        // Sort by the order in selectedIds
        $sorted = [];
        foreach ($selectedIds as $id) {
            if (isset($newsMap[$id])) {
                $sorted[] = $newsMap[$id];
            }
        }

        return $sorted;
    }

    /**
     * Find an alternative position when the preferred one is full
     *
     * @param array $categorized
     * @param string $preferredPosition
     * @return string|null
     */
    private function findAlternativePosition(array $categorized, string $preferredPosition): ?string
    {
        // Priority order for alternatives
        $alternatives = [
            'highlight' => ['normal', 'grid'],
            'normal' => ['grid', 'highlight'],
            'grid' => ['normal', 'highlight']
        ];

        foreach ($alternatives[$preferredPosition] ?? [] as $alternative) {
            if (count($categorized[$alternative]) < $this->categoryLimits[$alternative]) {
                return $alternative;
            }
        }

        return null;
    }

    /**
     * Validate newsletter content before sending
     *
     * @param Newsletter $newsletter
     * @return array Validation result with 'valid' and 'errors'
     */
    public function validateNewsletterContent(Newsletter $newsletter): array
    {
        $errors = [];

        // Check if newsletter has content
        if (empty($newsletter->getNewsIds())) {
            $errors[] = 'Newsletter has no news items';
        }

        // Check if HTML content is generated
        if (empty($newsletter->getHtmlContent())) {
            $errors[] = 'Newsletter HTML content has not been generated';
        }

        // Check header text
        if (empty(trim($newsletter->getHeaderText()))) {
            $errors[] = 'Newsletter header text is required';
        }

        // Validate categorized news
        $categorizedNews = $newsletter->getCategorizedNews();
        if (empty($categorizedNews)) {
            $errors[] = 'Newsletter has no categorized news items';
        } else {
            // Check if at least one category has items
            $hasContent = false;
            foreach ($categorizedNews as $category => $items) {
                if (!empty($items)) {
                    $hasContent = true;
                    break;
                }
            }

            if (!$hasContent) {
                $errors[] = 'Newsletter must have at least one news item in any category';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Calculate optimal distribution of news items across categories
     *
     * @param int $totalItems
     * @return array
     */
    public function calculateOptimalDistribution(int $totalItems): array
    {
        if ($totalItems <= 3) {
            return [
                'highlight' => $totalItems,
                'normal' => 0,
                'grid' => 0
            ];
        }

        if ($totalItems <= 6) {
            return [
                'highlight' => 3,
                'normal' => $totalItems - 3,
                'grid' => 0
            ];
        }

        if ($totalItems <= 12) {
            return [
                'highlight' => 3,
                'normal' => 6,
                'grid' => $totalItems - 9
            ];
        }

        // For more than 12 items, distribute evenly
        $remaining = $totalItems - 3; // Reserve 3 for highlights
        $normalCount = min(9, (int)ceil($remaining / 2));
        $gridCount = min(9, $remaining - $normalCount);

        return [
            'highlight' => 3,
            'normal' => $normalCount,
            'grid' => $gridCount
        ];
    }

    /**
     * Get newsletter content statistics
     *
     * @param Newsletter $newsletter
     * @return array
     */
    public function getContentStatistics(Newsletter $newsletter): array
    {
        $categorizedNews = $newsletter->getCategorizedNews();
        $stats = [
            'total' => 0,
            'by_category' => [],
            'by_research_line' => [],
            'with_images' => 0,
            'without_images' => 0
        ];

        foreach ($categorizedNews as $category => $newsItems) {
            $stats['by_category'][$category] = count($newsItems);
            $stats['total'] += count($newsItems);

            foreach ($newsItems as $news) {
                // Count by research line
                $researchLine = $news->getResearchLine() ?? 'Sin línea';
                if (!isset($stats['by_research_line'][$researchLine])) {
                    $stats['by_research_line'][$researchLine] = 0;
                }
                $stats['by_research_line'][$researchLine]++;

                // Count images
                if ($news->getFeaturedImageUrl()) {
                    $stats['with_images']++;
                } else {
                    $stats['without_images']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Recommend news items for newsletter based on criteria
     *
     * @param array<News> $availableNews
     * @param array $criteria
     * @return array<News>
     */
    public function recommendNewsItems(array $availableNews, array $criteria = []): array
    {
        // Default criteria
        $maxItems = $criteria['max_items'] ?? 21;
        $requireImage = $criteria['require_image'] ?? false;
        $prioritizeRecent = $criteria['prioritize_recent'] ?? true;
        $balanceResearchLines = $criteria['balance_research_lines'] ?? true;

        // Filter by image requirement
        if ($requireImage) {
            $availableNews = array_filter($availableNews, function (News $news) {
                return !empty($news->getFeaturedImageUrl());
            });
        }

        // Sort by date if prioritizing recent
        if ($prioritizeRecent) {
            usort($availableNews, function (News $a, News $b) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            });
        }

        // Balance by research lines
        if ($balanceResearchLines) {
            $availableNews = $this->balanceByResearchLine($availableNews, $maxItems);
        } else {
            // Simple slice if not balancing
            $availableNews = array_slice($availableNews, 0, $maxItems);
        }

        return $availableNews;
    }

    /**
     * Balance news selection by research line
     *
     * @param array<News> $newsItems
     * @param int $maxItems
     * @return array<News>
     */
    private function balanceByResearchLine(array $newsItems, int $maxItems): array
    {
        // Group by research line
        $grouped = [];
        foreach ($newsItems as $news) {
            $line = $news->getResearchLine() ?? 'other';
            if (!isset($grouped[$line])) {
                $grouped[$line] = [];
            }
            $grouped[$line][] = $news;
        }

        // Calculate items per line
        $numLines = count($grouped);
        $itemsPerLine = max(1, (int)floor($maxItems / $numLines));
        $remainder = $maxItems % $numLines;

        // Select items from each line
        $selected = [];
        $lineIndex = 0;

        foreach ($grouped as $line => $items) {
            $limit = $itemsPerLine;
            if ($lineIndex < $remainder) {
                $limit++; // Distribute remainder
            }

            $selected = array_merge(
                $selected,
                array_slice($items, 0, $limit)
            );

            $lineIndex++;
        }

        return array_slice($selected, 0, $maxItems);
    }

    /**
     * Get category limits
     *
     * @return array
     */
    public function getCategoryLimits(): array
    {
        return $this->categoryLimits;
    }

    /**
     * Get total capacity for newsletter
     *
     * @return int
     */
    public function getTotalCapacity(): int
    {
        return array_sum($this->categoryLimits);
    }
}
