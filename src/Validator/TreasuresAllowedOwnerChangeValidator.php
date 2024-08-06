<?php

namespace App\Validator;

use App\Entity\DragonTreasure;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TreasuresAllowedOwnerChangeValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        assert($constraint instanceof TreasuresAllowedOwnerChange);

        if (null === $value || '' === $value) {
            return;
        }

        assert($value instanceof Collection);

        $unitOfWork = $this->entityManager->getUnitOfWork();
        foreach ($value as $dragonTreasure) {
            assert($dragonTreasure instanceof DragonTreasure);

            $originalData = $unitOfWork->getOriginalEntityData($dragonTreasure);
            $originalId = $originalData['owner_id'];
            $newOwnerId = $dragonTreasure->getOwner()->getId();

            if ($this->security->isGranted('ROLE_ADMIN')) {
                return;
            }

            if (!$originalId || $originalId === $newOwnerId) {
                return;
            }

            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
