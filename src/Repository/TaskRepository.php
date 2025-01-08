<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\TaskException;
use App\Entity\Task;

final class TaskRepository extends BaseRepository
{
    public function checkAndGetTask(int $taskId, int $userId): Task
    {
        $query = '
            SELECT * FROM `tasks` 
            WHERE `id` = :id AND `userId` = :userId
        ';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('id', $taskId);
        $statement->bindParam('userId', $userId);
        $statement->execute();
        $task = $statement->fetchObject(Task::class);
        if (!$task) {
            throw new TaskException('Task not found.', 404);
        }

        return $task;
    }

    public function getAllTasks(): array
    {
        $query = 'SELECT * FROM `tasks` ORDER BY `id`';
        $statement = $this->getDb()->prepare($query);
        $statement->execute();

        return (array) $statement->fetchAll();
    }

    public function getTasksByUserId(int $userId): array
    {
        $query = 'SELECT * FROM `tasks` WHERE `userId` = :userId ORDER BY `id`';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('userId', $userId);
        $statement->execute();

        return (array) $statement->fetchAll();
    }

    public function getQueryTasksByPage(): string
    {
        return "
            SELECT *
            FROM `tasks`
            WHERE `userId` = :userId
            AND `name` LIKE CONCAT('%', :name, '%')
            AND `description` LIKE CONCAT('%', :description, '%')
            AND `status` LIKE CONCAT('%', :status, '%')
            ORDER BY `id`
        ";
    }

    public function getTasksByPage(
        int $userId,
        int $page,
        int $perPage,
        ?string $name,
        ?string $description,
        ?string $status
    ): array {
        $params = [
            'userId' => $userId,
            'name' => is_null($name) ? '' : $name,
            'description' => is_null($description) ? '' : $description,
            'status' => is_null($status) ? '' : $status,
        ];
        $query = $this->getQueryTasksByPage();
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('userId', $params['userId']);
        $statement->bindParam('name', $params['name']);
        $statement->bindParam('description', $params['description']);
        $statement->bindParam('status', $params['status']);
        $statement->execute();
        $total = $statement->rowCount();

        return $this->getResultsWithPagination(
            $query,
            $page,
            $perPage,
            $params,
            $total
        );
    }

    private function getSearchTasksQuery(?int $status): string
    {
        $statusQuery = '';
        if ($status === 0 || $status === 1) {
            $statusQuery = 'AND `status` = :status';
        }

        return "
            SELECT * FROM `tasks`
            WHERE `name` LIKE :name AND `userId` = :userId ${statusQuery}
            ORDER BY `id`
        ";
    }

    public function search(string $tasksName, int $userId, ?int $status): array
    {
        $query = $this->getSearchTasksQuery($status);
        $name = '%' . $tasksName . '%';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('name', $name);
        $statement->bindParam('userId', $userId);
        if ($status === 0 || $status === 1) {
            $statement->bindParam('status', $status);
        }
        $statement->execute();
        $tasks = (array) $statement->fetchAll();
        if (!$tasks) {
            $message = 'No Tasks were found with that name.';
            throw new TaskException($message, 404);
        }

        return $tasks;
    }

    public function create(Task $task): Task
    {
        $query = '
            INSERT INTO `tasks`
                (`name`, `description`, `status`, `userId`, `createdAt`)
            VALUES
                (:name, :description, :status, :userId, :createdAt)
        ';
        $statement = $this->getDb()->prepare($query);
        $name = $task->getName();
        $desc = $task->getDescription();
        $status = $task->getStatus();
        $userId = $task->getUserId();
        $created = $task->getCreatedAt();
        $statement->bindParam('name', $name);
        $statement->bindParam('description', $desc);
        $statement->bindParam('status', $status);
        $statement->bindParam('userId', $userId);
        $statement->bindParam('createdAt', $created);
        $statement->execute();

        $taskId = (int) $this->getDb()->lastInsertId();

        return $this->checkAndGetTask((int) $taskId, (int) $userId);
    }

    public function update(Task $task): Task
    {
        $query = '
            UPDATE `tasks`
            SET 
                `name` = :name, 
                `description` = :description, 
                `status` = :status, 
                `updatedAt` = :updatedAt
            WHERE `id` = :id AND `userId` = :userId
        ';
        $statement = $this->getDb()->prepare($query);
        $id = $task->getId();
        $name = $task->getName();
        $desc = $task->getDescription();
        $status = $task->getStatus();
        $userId = $task->getUserId();
        $updated = $task->getUpdatedAt();
        $statement->bindParam('id', $id);
        $statement->bindParam('name', $name);
        $statement->bindParam('description', $desc);
        $statement->bindParam('status', $status);
        $statement->bindParam('userId', $userId);
        $statement->bindParam('updatedAt', $updated);
        $statement->execute();

        return $this->checkAndGetTask((int) $id, (int) $userId);
    }

    public function delete(int $taskId, int $userId): void
    {
        $query = 'DELETE FROM `tasks` WHERE `id` = :id AND `userId` = :userId';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('id', $taskId);
        $statement->bindParam('userId', $userId);
        $statement->execute();
    }
}
