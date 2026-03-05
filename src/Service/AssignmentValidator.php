<?php

namespace App\Service;

use InvalidArgumentException;

class AssignmentValidator
{
    private const VALID_PRIORITIES = ['low', 'medium', 'high'];
    private const VALID_STATUSES = ['todo', 'in_progress', 'done', 'cancelled'];

    public function validate(array $data): array
    {
        if (empty($data['titre'])) {
            throw new InvalidArgumentException('Title is required');
        }

        if (empty($data['description'])) {
            throw new InvalidArgumentException('Description is required');
        }

        if (isset($data['dateDebut']) && isset($data['dateFin'])) {
            $dateDebut = is_string($data['dateDebut']) ? new \DateTime($data['dateDebut']) : $data['dateDebut'];
            $dateFin = is_string($data['dateFin']) ? new \DateTime($data['dateFin']) : $data['dateFin'];

            if ($dateFin < $dateDebut) {
                throw new InvalidArgumentException('End date must be after start date');
            }
        }

        if (isset($data['priorite']) && !in_array($data['priorite'], self::VALID_PRIORITIES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Priority must be one of: %s',
                implode(', ', self::VALID_PRIORITIES)
            ));
        }

        if (isset($data['statut']) && !in_array($data['statut'], self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Status must be one of: %s',
                implode(', ', self::VALID_STATUSES)
            ));
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }
}
