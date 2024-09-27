<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\DailyQuest;
use App\ApiResource\QuestTreasure;
use App\Enum\DailyQuestStatusEnum;
use App\Repository\DragonTreasureRepository;

class DailyQuestStateProvider implements ProviderInterface
{
    public function __construct(
        private DragonTreasureRepository $treasureRepository,
        private Pagination $pagination
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $currentPage = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
            $offset = $this->pagination->getOffset($operation, $context);
            $totalItems = $this->countTotalQuests();

            $quests = $this->createQuests($offset, $itemsPerPage);

            return new TraversablePaginator(
                new \ArrayIterator($quests),
                $currentPage,
                $itemsPerPage,
                $totalItems
            );
        }

        $quests = $this->createQuests(0, $this->countTotalQuests());
        return $quests[$uriVariables['dayString']];
    }

    /**
     * @throws \Exception
     */
    private function createQuests(int $offset, int $limit = 50): array
    {
        $treasures = $this->treasureRepository->findBy([], limit: 10);

        $quests = [];
        for ($i = $offset; $i < ($offset + $limit); $i++) {
            $quest = new DailyQuest(new \DateTimeImmutable(sprintf('- %d days', $i)));
            $quest->questName = sprintf('Quest %d', $i);
            $quest->description = sprintf('Description %d', $i);
            $quest->difficultyLevel = $i % 10;
            $quest->status = $i % 2 === 0 ? DailyQuestStatusEnum::ACTIVE : DailyQuestStatusEnum::COMPLETED;
            $quest->lastUpdated = new \DateTimeImmutable(sprintf('- %d days', rand(10, 100)));

            $randomTreasuresKeys = array_rand($treasures, rand(1, 3));
            $randomTreasures = array_map(fn($key) => $treasures[$key], (array) $randomTreasuresKeys);
            $questTreasures = [];
            foreach ($randomTreasures as $treasure) {
                $questTreasures[] = new QuestTreasure(
                    $treasure->getName(),
                    $treasure->getValue(),
                    $treasure->getCoolFactor()
                );
            }
            $quest->treasures = $questTreasures;

            $quests[$quest->getDayString()] = $quest;
        }
        return $quests;
    }

    private function countTotalQuests(): int
    {
        return 50;
    }
}
