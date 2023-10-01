Marty MacFly
==================

Marty MacFly - Back to the Future to write your Symfony fixtures
Marty MacFly allows you to quickly and easily create fixtures to simplify development and testing for Symfony.

## Main features


## Installing

[PHP](https://php.net) 8.0+ and [Composer](https://getcomposer.org) are required.

```bash
composer req --dev Marty/McFly
```

You need to create a fixture file per entity and extend `Marty\McFly\Fixture` instead of `Doctrine\Bundle\FixturesBundle\Fixture`.

```php
<?php
// src/DataFixtures/CompanyFixtures.php

namespace App\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Marty\McFly\Fixture;

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
```

By adding `Marty\McFly\Fixture`, you must adhere to the `Marty\McFly\Interface\CreateInterface` interface and add a `generate()` function.
> ⚠️ The `create()` function was renamed to `generate()` between v1 and v2 to avoid breaking changes. The `create()` function is still compatible but it is recommended to migrate to `generate()` to benefit from the new features.

```php
<?php
// src/DataFixtures/CompanyFixtures.php

namespace App\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Marty\McFly\Fixture;

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $this->generate();

        $manager->flush();
    }

    public function generate(?array $properties = null, array|string|null $references = null): object
    {
        // TODO: Implement generate() method.
        throw new \RuntimeException("The generate() method is not implemented.");
    }

    public function create(array|string $references = null): object
    {

    }
}
```

Configure your template (`generate()` is a factory, work with Reflection without setters or _construct)

```php
<?php
// src/DataFixtures/CompanyFixtures.php

namespace App\DataFixtures;

use App\Entity\Company;
use Doctrine\Persistence\ObjectManager;
use Marty\McFly\Fixture;

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a random Company, persist it (for database), and automatically add a reference to use it in other fixtures.
        $this->generate();

        // Create 10 randoms Companies to "Paris" (Since 2.0)
        $this->generateMany(10, [
            'city' => 'Paris',
            'postalCode' => '75000'
        ]);

        // Finally, flush to the database
        $manager->flush();
    }

    public function generate(?array $properties = null, array|string|null $references = null): object
    {
        return $this->createAndSave(Company::class, $properties,
            [
                'name'       => self::$faker->company(),
                'address'    => self::$faker->address(),
                'city'       => self::$faker->city(),
                'postalCode' => self::$faker->postcode(),
            ],
            $references
        );
    }

}
```

Configure your template : The alternative style (using setter if you have) :

```php
<?php
// src/DataFixtures/CompanyFixtures.php

namespace App\DataFixtures;

use App\Entity\Company;
use Doctrine\Persistence\ObjectManager;
use Marty\McFly\Fixture;

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a random Company, persist it (for database), and automatically add a reference to use it in other fixtures.
        $this->generate();

        // Alternative : Create 10 randoms Companies to "Paris" with setter
        for ($i=0 ; $i<10 ; $i++) {
            $this->generate()
                ->setCity('Paris')
                ->setPostalCode('75000');
        }

        // Finally, flush to the database
        $manager->flush();
    }

    public function generate(?array $properties = null, array|string|null $references = null): object
    {
        $company = (new Company())
            ->setName(self::getFaker()->company())
            ->setAddress(self::getFaker()->address())
            ->setCity(self::getFaker()->city())
            ->setPostalCode(self::getFaker()->postcode());

        $this->save($company, $references);

        return $company;
    }
}
```


## Usage

Create a random entity

```php
<?php

// ...

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a random Company
        $this->generate();

        // Finally, flush to the database
        $manager->flush();
    }

    // ... generate() definition
}

```

Creating an entity by setting only the necessary properties (recommandation style).

```php
<?php

// ...

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a random Company to "Paris"
        $this->generate([
            'city'       => 'Paris',
            'postalCode' => '75000',
        ]);

        // Finally, flush to the database
        $manager->flush();
    }

    // ... generate() definition
}
```

Creating an entity by setting only the necessary properties (alternative style).
> ⚠️ With this syntax, the entity is create and change after.

```php
<?php

// ...

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a random Company to "Paris"
        $this->generate()
            ->setCity('Paris')
            ->setPostalCode('75000');

        // Finally, flush to the database
        $manager->flush();
    }

    // ... generate() definition
}
```

Create multiple entities while customizing certain properties.

```php
<?php

// ...

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        // Create 10 random Company to "Paris"
        $this->generateMany(10, [
            'city'       => 'Paris',
            'postalCode' => '75000',
        ]);

        // Finally, flush to the database
        $manager->flush();
    }

    // ... generate() definition
}

```

Create multiple entities while customizing certain properties (alternative.

```php
<?php

// ...

class CompanyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        // Create 10 random Company to "Paris"
        for ($i=0 ; $i<10 ; $i++) {
            $this->generate()
                ->setCity('Hill Valley')
            ;
        }

        // Finally, flush to the database
        $manager->flush();
    }

    // ... generate() definition
}

```

## Dependencies


```php

<?php

namespace App\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Marty\McFly\Fixture;
use App\Entity\Company;

class UserFixture extends Fixture implements DependentFixtureInterface
{

    public function load(ObjectManager $manager): void
    {
        // Use this function to loop through all companies and create users for them.
        /** @var array<Company> $companies */
        $companies = $this->getReferencesByClass(Company::class);

        // Need a random company? Use this method.
        /** @var Company $company */
        $company = $this->getRandomReferenceByClass(Company::class);

    }

    public function create(string|array $references = null): User
    {
        // You can also use it in the template to add a default random company.
        /** @var Company $company */
        $company = $this->getRandomReferenceByClass(Company::class);

        // ..
    }

    public function getDependencies(): array
    {
        return [
            CompanyFixture::class,
        ];
    }
}
```

## References, counter, enum an random values

```php
<?php
// --

class InvoiceFixture extends Fixture implements DependentFixtureInterface
{

    // --

    public function generate(?array $properties = null, array|string|null $references = null): Invoice
    {
        $invoice = $this->createAndSave(Invoice::class, $properties,
            [
                'createdAt' => new DateTimeImmutable(),
                'number'    => InvoiceFixture::count(),                                 // the current number, auto-incrementation on save()
                'user'      => $this->getRandomReferenceByClass(User::class),           // a random User
                'status'    => self::randomValue(Status::cases()),                      // randomly a value of Status Enum,
                'confirmed' => self::randomValue([True, False]),                        // randomly True or False
                'comment'   => self::randomValue([null, self::getFaker()->sentence()]), // Add random sentence or randomly NULL
                'product'
            ],
            $references
        );

        // Add RandomProduct
        for ($i=0 ; $i<10 ; $i++) {
            /** @var Product $product */
            $randomProduct = $this->getRandomReferenceByClass(Product::class);
            $invoice->addProduct($randomProduct);
        }

        return $invoice;
    }

    // --
}
```

## Credits

- Arnaud Lemercier is based on [Wixiweb](https://wixiweb.fr).

## License

Marty MacFly is licensed under [The MIT License (MIT)](LICENSE).
