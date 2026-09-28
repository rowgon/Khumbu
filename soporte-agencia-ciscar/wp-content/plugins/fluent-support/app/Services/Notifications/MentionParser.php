<?php

namespace FluentSupport\App\Services\Notifications;

class MentionParser
{
    /**
     * Extract agent IDs from message content.
     *
     * Handles two mention formats:
     *   - Rich editor:       <span class="fs_agent_mention" data-mention-username="7">
     *   - Plain-text editor: @[7:Display Name]
     *
     * @param string $content
     * @return array Integer agent IDs.
     */
    public function extractMentionIds($content)
    {
        $content = (string) $content;

        if (!$content) {
            return [];
        }

        $ids = [];

        // Rich editor — span with numeric data-mention-username.
        if (stripos($content, 'data-mention-username') !== false) {
            preg_match_all(
                '/<span\b[^>]*\bclass=["\'][^"\']*\bfs_agent_mention\b[^"\']*["\'][^>]*\bdata-mention-username=["\'](\d+)["\'][^>]*>/i',
                $content,
                $spanMatches
            );

            foreach ($spanMatches[1] ?? [] as $id) {
                $ids[] = (int) $id;
            }
        }

        // Plain-text editor — @[agentId:Display Name] token.
        if (strpos($content, '@[') !== false) {
            preg_match_all('/@\[(\d+):[^\]]+\]/', $content, $tokenMatches);

            foreach ($tokenMatches[1] ?? [] as $id) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
