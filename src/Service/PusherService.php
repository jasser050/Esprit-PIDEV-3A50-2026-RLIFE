<?php

namespace App\Service;

use Pusher\Pusher;

class PusherService
{
    private Pusher $pusher;

    public function __construct(
        string $appId,
        string $key,
        string $secret,
        string $cluster
    ) {
        $this->pusher = new Pusher(
            $key,
            $secret,
            $appId,
            [
                'cluster' => $cluster,
                'useTLS' => true,
                'curl_options' => [
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0
                ]
            ]
        );
    }

    public function getPusher(): Pusher
    {
        return $this->pusher;
    }

    public function notifyProjectShared(int $projectId, string $projectTitle, string $sharedWith, string $sharedBy, string $role): void
    {
        $this->pusher->trigger('project-channel-' . $projectId, 'project-shared', [
            'project_id' => $projectId,
            'project_title' => $projectTitle,
            'shared_with' => $sharedWith,
            'shared_by' => $sharedBy,
            'role' => $role,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function notifyTaskAssigned(int $taskId, string $taskTitle, string $assignedTo, string $assignedBy): void
    {
        $this->pusher->trigger('task-channel-' . $taskId, 'task-assigned', [
            'task_id' => $taskId,
            'task_title' => $taskTitle,
            'assigned_to' => $assignedTo,
            'assigned_by' => $assignedBy,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function notifyNewComment(int $taskId, int $commentId, string $author, string $content, string $createdAt): void
    {
        $this->pusher->trigger('comments-channel-' . $taskId, 'new-comment', [
            'comment_id' => $commentId,
            'task_id' => $taskId,
            'author' => $author,
            'content' => $content,
            'created_at' => $createdAt,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function notifyCommentDeleted(int $taskId, int $commentId): void
    {
        $this->pusher->trigger('comments-channel-' . $taskId, 'comment-deleted', [
            'comment_id' => $commentId,
            'task_id' => $taskId,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    // Nouvelles méthodes pour notifications utilisateur (canaux privés)
    public function notifyUserProjectShared(int $userId, int $projectId, string $projectTitle, string $sharedBy, string $role): void
    {
        $this->pusher->trigger('private-user-' . $userId, 'project-shared', [
            'project_id' => $projectId,
            'project_title' => $projectTitle,
            'shared_by' => $sharedBy,
            'role' => $role,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function notifyUserTaskAssigned(int $userId, int $taskId, string $taskTitle, string $assignedBy): void
    {
        $this->pusher->trigger('private-user-' . $userId, 'task-assigned', [
            'task_id' => $taskId,
            'task_title' => $taskTitle,
            'assigned_by' => $assignedBy,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function broadcast(string $channel, string $event, array $data): void
    {
        $this->pusher->trigger($channel, $event, $data);
    }
}