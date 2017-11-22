### Advanced: External (un)serializers and inheritance

Inheritance makes things harder. If you want to serialize subtypes subtree you will need to guarantee that you are able to serialize really all of them. Even those that are not yet implement now. This is very hard for external serializers. Right?

That is because newly created children does not known that they will be serializer so they does not provide same properties, adds their own, etc.

**Proper support for inheritance comes automatically and is intuitive when you use *stateful* interface.**



#### What if external serializers would support matching all subtypes by default?

Lets imagine there is an magic annotation `@allowMatchingOfSubtypes` which will call (un)serializer whenever object is exact type or any subtype.

````php
<?php
use Grifart\Stateful\ExternalSerializer\SerializerList;use Grifart\Stateful\State;use Grifart\Stateful\PayloadProcessor;use Grifart\Stateful\Mapper\TrivialMapper;

$processor = new PayloadProcessor(
    new TrivialMapper(),
    SerializerList::from([
    	// serializer: DateTimeInterface => ObjectState
    	// 
    	// 1. matches all objects that implements DateTimeInterface
    	// 2. provides ObjectState for them
    	/** @matchSubtypes */
        function(\DateTimeInterface $dateTime): State {
            return State::from($dateTime, 1, [
                'date' => $dateTime->format('c')
            ]);
        },
        
        // unserializer: ObjectState => DateTimeInterface
        //
        // This unserializer will be triggered for all objects that implements \DateTimeInterface.
        // However you must always return original class type (e.g. \DateTime or \DateTimeImmutable).
        // This is also checked by PayloadProcessor. If you return different class that was serializer you get an exception.
        /** @matchSubtypes */ 
        function (State $state): \DateTimeInterface {
            $class = $state->getClassName();
            return new $class($state['date']);
        },
    ])
);
````

And now bad guy comes and implements following object:

````php
<?php
class MyDatetime extends DateTime {
	private $myVariable = 42;
}
````

Is `MyDatetime::$myVariable` going to be serialized by serializer provided above? No. Whops.

To make this less painful, at least you get exception when you are **serializing** your object, because `State` compares provided state fields with an original object and if there is field silently ignored, you will get an exception in serialization procedure. This prevents data corruption again.

So be careful when using `@allowMatchingOfSubtypes`.

To fix serialization of `MyDateTime` you can:

- prepend serializer for more specific type before general serializer (e.g. serializer for `MyDatetime`)
- Implement `Stateful` into `MyDateTime`; `Stateful` interface has **higher priority** then external serializers

Internal: (Un)serializers are evaluated from top to bottom. Always place most specific (un)serializers to the top and general to bottom.

Advanced: (us)serializers does not need to have 1:1 relationship. You can provide serializer for and interface and unserializer for implementations. This will work, but please note that you serialized object state must be compatible with all deserializers.
