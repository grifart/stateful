# Payload serialization library

Serializes any object into primitive types and arrays. Provides support for serialized data versioning. Is strict as possible to prevent broken serialized data.

- **strictly checks** that you have serialized all object fields
    - ignored fields must be explicitly marked as ignored on serialization
- **versioning** string must be checked on deserialization
    - this guarantees stable object state interface even when there are more versions object stored
- **class name routing** decouples PHP class name for serialized one
    - this allows you to refactor your code (e.g. rename namespace) without breaking deserializers.



## Architecture overview

**Payload** is primitive representation of an object. `Payload` is serializable to JSON, XML or any other format that supports objects with named keys, strings, nulls and integers.

**Object state** represents data that object needs to be unserialized.

**Payload processor** converts objects into `Payload` and back using their `State`

**Stateful** is an interface for objects that are state-aware, therefore they are able to provide *object state* and reconstruct themselves from given *object state*.

**External (un)serializer** is used for objects that needs to be serialized but does not provide `Stateful` interface. Typically PHP internal objects or objects from external libraries. (un)serializer is simple closure that converts original object to `State` and back. They are passed to PayloadProcessor constructor.

Please note that **every object that needs to be serialized** at some point does **need to have *external (un)serializer* or must implement *Stateful* interface**.



## What is an *object state*?

`State` encapsulates state of an object that defines object current state and that is need to reconstruct an original object.

- can contain [scalar](http://php.netvisioning/language.types.intro.php) types
- can contain `NULL`s
- can contain array
- can contain any other object
- cannot contain [resource](http://php.net/manual/en/resource.php)s


## How to provide an *object state*?

- by implementing **StatefulObject interface** which contains two methods:
    - `object->_getState(): State` for extracting object state
    - `object::_fromState(State $state): object` for recreating object from state
- by creating and **external (un)serializers** for types that don't/cannot implement *StatefulObject interface* and registering the (un)serializers in `PayloadProcessor` via its constructor




### Implementing the *Stateful* interface

````php
<?php
use Grifart\Stateful\Stateful; use Grifart\Stateful\State;

final class Address implements Stateful {
	private $street;
	private $houseNumber;
	private $city;
	
	// constructor, getters ...
	
	public function _getState(): State {
		return State::from($this, /* version */ 1, [
			'street' => $this->street,
			'houseNumber' => $this->houseNumber,
			'city' => $this->city,
        ]);
	}
 
    public static function _fromState(State $state): self {
		assert($state->getVersion() === 1);
		
        /** @var self $me */
        $me = $state->makeAnEmptyObject(self::class);
        $me->street = $state['street'];
        $me->houseNumber = $state['houseNumber'];
        $me->city = $state['city'];
        return $me;
    }
}

````



### Implementing the *Stateful* interface with trait for simple objects

For simple objects that does **NOT change over time** you can use Trait which is bundled with this library. It simply serializes all properties that are available in object.

During deserialization the Trait checks that all properties of object are still there and were not renamed. This prevents silent data corruption.

````php
<?php

use Grifart\Stateful\Stateful; use Grifart\Stateful\SimpleStatefulTrait;

final class Address implements Stateful {
	private $street;
	private $houseNumber;
	private $city;
		
	// constructor, getters ...
	use SimpleStatefulTrait; // implements Stateful interface for you
}

````



## Providing external serializers for non-stateful objects

As opposed to `Stateful` interface this methods is used when object itself is not able to say what is needed to be serialized. This methods requires you to pass (un)serializers for given object types to PayloadProcessor constructor.

Please note that **exact type match** is needed to (de)serializer be choosen by payload processor. If you provide **child type** to (un)serializer it **will NOT be used**. See [external-serializers-and-inheritance](../ExternalSerializersAndInheritance.md).

Note: External serializers are called ony when object does not implement *Stateful* interface


````php
<?php
use Grifart\Stateful\Exceptions\VersionMismatchException;use Grifart\Stateful\ExternalSerializer\SerializerList;use Grifart\Stateful\State; use Grifart\Stateful\StateBuilder;use Grifart\Stateful\PayloadProcessor; use Grifart\Stateful\Mapper\TrivialMapper;

$processor = new PayloadProcessor(
    new TrivialMapper(),
    SerializerList::from([
    	/** serializer for \DateTime */
        function(\DateTime $dateTime): State {
            return State::from($dateTime, 1, [
                'date' => $dateTime->format('c')
            ]);
        },
        /** unserializer for \DateTime */
        function (State $state): \DateTime {
        	assert($state->getVersion() === 1);
            return new \DateTime($state['date']);
        },
        
        /** serializer for \DateTimeImmutable */
        function(\DateTimeImmutable $dateTime): State { // serializer
            return StateBuilder::from($dateTime)
                ->version(1)
                ->field('date', $dateTime->format('c'))
                ->build();
        },
        /** unserializer for \DateTimeImmutable */
        function (State $state): \DateTimeImmutable {
        	switch($state->getVersion()) {
        		case 1:
                    return new \DateTimeImmutable($state['date']);
        	}
        	throw VersionMismatchException::objectStateVersionNotSupported($state);
        },
    ])
);
````



## Versioning of serialized objects

Serialized objects are typically stored for a long time. This means there is need to support evolution of your objects.

This is achieved by the convention that every serializer must provide object state version number and unserializer must check this version number.

It is recommended to use this template:

````php
<?php
use Grifart\Stateful\Exceptions\VersionMismatchException;use Grifart\Stateful\Stateful; use Grifart\Stateful\State;use Grifart\Stateful\StateBuilder;

final class SomeClass implements Stateful {

	private $myProp = 42;
	
	public function _getState(): State
	{
		return StateBuilder::from($this)
			->version(1)
			->field('serializedNameForMyProp',$this->myProp)
			->ignore('myProp') // myProp was serialized with different name --> is considered not serialized
			->build();
	}

	public static function _fromState(State $state): self
	{
		/** @var self $me */
        $me = $state->makeAnEmptyObject(self::class); // without constructor

		switch($state->getVersion()) {
			case 1:
                $me->myProp = $state['serializedNameForMyProp'];
				return $me;
		}
		throw VersionMismatchException::objectStateVersionNotSupported($state);
	}
	
}

````

If you think that your class will not be changed often, you can use following minimal unserializer implementation:

````php
<?php
use Grifart\Stateful\State;

function(State $state): \DateTimeImmutable
{
    assert($state->getVersion() === 1); // assert ensures that you will know that this code needs to be updated
    return new \DateTimeImmutable($state['dateSerialized']);
}
````



## Class name mapping

Object names in serialized data does not need to be class names. Motivation for NOT having same class name and *transfer object name* are following:

- you can version your serialized object names
- you can move/rename your classes without making BC break
- serialized names can be shorter and save space

### Example name mapping

| Class name                                  | Serialized name               |
|:--------------------------------------------|:------------------------------|
| App\Model\Domain\Order\Event\OrderWasPlaced | V1.Event.Order.OrderWasPlaced |
| App\Model\Domain\Order\Document\MyDocument  | V1.Event.Document.MyDocument  |

It is good idea to prepend version number to serialized class name. This provides you more flexibility in the future and allows you to make more radical changes in your project without BC break with your serialized data.

**Note:** *Serialized name version* is different thing that *object state version*.

You can continuously move to new naming and still support old names by providing on-way name router. This allows you keep your legacy names organized and separate from new ones.

## External serializers with matching sub-types

If
 - [ ] you cannot implement `Stateful` interface on base class (e.g. is in 3rd party code)
 - [ ] implementing external serializer for every type becomes impractical
 - [ ] super-type constraints all sub-types enough, that you are able to extract state of every possible class instance of any sub-type
 - [ ] you are able to extract state ≫only≪ using super-type interface

If you answered 'yes' to all conditions above, you can use external serializer with `@matchSubtypes` annotation. This allows usage of annotated serializer for all sub-types.

📌 Use external serializers with `@matchSubtypes` as the last option 
as there is no way on the type-system level to check if extracted state is complete for every possible subtype

### Known valid use-cases:

**Enumeration classes**

- ✓ There are many enum types in the app and they has a common base class or an interface. 
- ✓ Enum base class is in another library so we cannot implement Stateful interface on the base class.
- ✓ Enumeration by definition says that if we remember value identifier, we can reconstruct any value without loosing information.


# Further reading

- [Using external serializers with inheritance and interfaces](ExternalSerializersAndInheritance.md)

