<?php
/** @noinspection UnknownInspectionInspection */

/** @noinspection GrazieInspection */

namespace Marty\McFly\Interface;

interface CreateInterface
{
    /**
     * Factory qui créé des entités persistées, pré-configurés de manière aléatoire. Les cas particuliers seront des surcharges
     * L'index 'ID:$id' est créé automatiquement pour récupérer par l'id de l'entité
     * La référence peut être une chaine comme '1', 'default', ou un tableau ['indexA', 'indexB']
     * Deux objets de classe différentes peuvent avoir le même index
     *
     * @todo : Il s'agit d'un index et non d'un TAG, il ne peut pas y avoir plusieurs instances de la même entités avec un même index.
     * @example $this->create() // Créée une entité aléatoire
     * @example $this->create()->setPrice(100) // Créée une entité aléatoire avec un prix fixé à 100
     * @example $this->create()->setPrice(100)->setDuration(60) // Créée une entité aléatoire avec un prix fixé à 100 et une durée à 60
     * @example $this->create(1) // Créée une entité aléatoire sur l'index '1'
     * @example $this->create('price:100')->setPrice(100) // Créée une entité aléatoire sur l'index 'price:100'
     * @example $this->create(['price:100', 'duration:60'])->setPrice(100)->setDuration(60) // Créée une entité aléatoire sur l'index 'price:100' et 'duration:60'
     */
    public function create(array $properties, string|array $references = null): object;

    /**
     * Récupère le nombre d'objet créé pour itéré facilement dessus
     */
    public static function count():int;
}
