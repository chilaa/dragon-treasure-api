<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\UserApi;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EntityClassDtoStateProcessor implements ProcessorInterface
{
    function __construct(
        private UserRepository $repository,
        #[Autowire(service: PersistProcessor::class)] private PersistProcessor $persistProcessor,
        #[Autowire(service: RemoveProcessor::class)] private RemoveProcessor $removeProcessor,
        private UserPasswordHasherInterface $passwordHasher
    ) {

    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof UserApi);

        $entity = $this->mapDtoToEntity($data);

        if ($operation instanceof DeleteOperationInterface) {
            $this->removeProcessor->process($entity, $operation, $uriVariables, $context);

            return null;
        }

        $this->persistProcessor->process($entity, $operation, $uriVariables, $context);
        $data->id = $entity->getId();

        return $data;
    }

    private function mapDtoToEntity(object $dto): object
    {
        assert($dto instanceof UserApi);
        if ($dto->id) {
            $entity = $this->repository->find($dto->id);

            if (!$entity) {
                throw new \Exception(sprintf("Entity %d not found.", $dto->id));
            }
        } else {
            $entity = new User();
        }

        $entity->setUsername($dto->username);
        $entity->setEmail($dto->email);
        if ($dto->password) {
            $entity->setPassword($this->passwordHasher->hashPassword($entity, $dto->password));
        }

        return $entity;
    }
}
