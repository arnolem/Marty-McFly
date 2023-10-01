<?php

namespace Marty\McFly;

use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Faker\Factory;
use Faker\Generator;
use Marty\McFly\Interface\CreateInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

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
     * Create an entity from array (by reflection) with default value, save it and reference it automatically for retrieve
     */
    public function createAndSave(
        string $className,
        array $properties,
        array $default = null,
        array|string $references = null
    ) {

        $entity = $this->createFromProperties($className, $properties, $default);

        $this->save($entity);

        return $entity;
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
     * Create an instance of the entity with Reflection for setting all properties without setter or constructor
     */
    public function createFromProperties(string $className, array $properties, array $default = null)
    {

        // Fusionne avec les valeurs par défaut
        $properties = array_merge($default, $properties);

        // Créez une instance de la classe ReflectionClass
        $reflectionClass = new ReflectionClass($className);

        // Utilisez la méthode newInstance() pour créer une instance de la classe
        $instance = $reflectionClass->newInstance();

        foreach ($properties as $property => $value) {
            $reflectionProperty = new ReflectionProperty($instance, $property);
            $reflectionProperty->setAccessible(true); // Rend la propriété accessible
            $reflectionProperty->setValue($instance, $value); // Définit la valeur de la propriété
        }

        return $instance;
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
