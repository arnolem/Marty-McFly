<?php

namespace Marty\McFly;

use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Faker\Factory;
use Faker\Generator;
use Marty\McFly\Interface\CreateInterface;

abstract class Fixture extends DoctrineFixture implements CreateInterface
{
    protected static Generator $faker;
    protected static int $count = 0;

    public function __construct()
    {
        self::$faker = Factory::create('fr_FR');
    }

    public static function count(): int
    {
        return self::$count;
    }

    protected static function setFaker(Generator $faker): void
    {
        self::$faker = $faker;
    }

    protected static function getFaker(): Generator
    {
        return self::$faker;
    }

    /**
     * Add or Fail
     */
    public function addReference($name, $object): void
    {
        $reference = $this->uniqueRef($name, $object::class);
        parent::addReference($reference, $object);
    }

    private function uniqueRef($name, $class): string
    {
        return $class . ':' . $name;
    }

    public function hasReference($name, ?string $class = null): bool
    {
        $reference = $this->uniqueRef($name, $class);

        return parent::hasReference($reference, $class);
    }

    public function getReferences(): array
    {
        return $this->referenceRepository->getReferences();
    }

    public function getRandomReferenceByClass(string $class): object
    {
        $referenceByClass = $this->getReferencesByClass($class);
        $object           = self::randomValue($referenceByClass);
        if ($object === null) {
            throw new RuntimeException("No reference by $class");
        }

        return $object;
    }

    /**
     * @return array<object>
     */
    public function getReferencesByClass(string $class): array
    {

        $referencesByClass = $this->referenceRepository->getReferencesByClass();
        $references        = $referencesByClass[$class];

        $return = [];

        foreach ($references as $key => $reference) {
            $pattern = '/^' . preg_quote($class, '/') . ':\d+$/';
            if (preg_match($pattern, $key)) {
                $return[] = parent::getReference($key, $class);
            }
        }

        return $return;

    }

    /**
     * Get by classname and ID
     */
    public function getReference($name, $class = null): ?object
    {
        $reference = $this->uniqueRef($name, $class);

        return parent::getReference($reference, $class);
    }

    /**
     * Helper pour tirer aléatoirement une valeur dans un tableau
     */
    public static function randomValue(array $array)
    {
        if (empty($array)) {
            return null;
        }

        return $array[array_rand($array)];
    }

    /**
     * Persist dans l'ObjectManager, Ajoute une référence automatique et des références manuelles
     */
    protected function save($entity, string|array $references = null): void
    {
        // Auto-save
        $manager = $this->referenceRepository->getManager();
        $manager->persist($entity);

        // Le premier créé à la référence "default" sauf si un autre est déclaré
        if (self::$count === 0) {
            $this->setReference('default', $entity);
        }

        // Auto-Reference par le numéro (Remplace le $i)
        $this->setReference(self::$count, $entity);

        // Ajoute des références manuelles
        if ($references !== null) {
            if ( ! is_array($references)) {
                $references = [$references];
            }
            foreach ($references as $reference) {
                $this->setReference($reference, $entity);
            }
        }

        // Compte le nombre d'entités créées
        self::$count++;
    }

    /**
     * Add or update
     */
    public function setReference($name, $object): void
    {
        $reference = $this->uniqueRef($name, $object::class);
        parent::setReference($reference, $object);
    }

}
