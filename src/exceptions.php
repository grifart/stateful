<?php declare(strict_types = 1);

namespace Grifart\Stateful;

use Grifart\Stateful\PayloadProcessor;
use Grifart\Stateful\State;
use Grifart\Stateful\Stateful;


// library root exceptions:
abstract class RuntimeException extends \RuntimeException {}
class UsageException extends \LogicException {}

// ----------- Runtime exceptions (are part of public API therefore changing them changes public API) ------------------

	class VersionMismatchException extends RuntimeException {
		public static function versionDoesNotMatch(State $state, array $supportedVersions = []): self
		{
			$className = $state->getClassName();
			$providedVersion = $state->getVersion();
			$supportedVersionsString = empty($supportedVersions) ? '' :
				'(' . implode(', ', $supportedVersions) . ')';

			return new self(
				"Object state version check failed for object '$className'. "
				. "Version required by deserializer$supportedVersionsString is not equal to provided object state version ($providedVersion)."
			);
		}

		public static function objectStateVersionNotSupported(State $state): self
		{
			return self::versionDoesNotMatch($state);
		}
	}

	/** Errors when converting Payload --> objects */
	abstract class PayloadParserException extends RuntimeException {}

		final class ClassNameMappingException extends PayloadParserException {

			public static function cannotConvertTransferNameToClassName(string $transferName): self
			{
				return new self("Cannot convert transfer class name $transferName to fully qualified class name. Did you registered it into name mapper?");
			}

		}

		final class ClassNotFoundException extends PayloadParserException {

			public static function classNameDeliverFromTransferName(string $className, string $transferName): self
			{
				return new self("Class '$className' has not been found in runtime. Class name was derived from $transferName.");
			}

		}

		final class NoAppropriateDeserializerFoundException extends PayloadParserException {

			public static function for(string $className): self
			{
				return new self(
					  "Cannot reconstructFromState '$className', no deserializer found. "
					. 'Object is not stateful and there are no matching external deserializers found. '
					. 'External serializer can match subtypes. Please read docs, there are some gotchas '
					. 'that you need to know about before you use this feature.'
				);
			}


			public static function unknownSerializationVersion(): NoAppropriateDeserializerFoundException
			{
				return new self('Unknown version for deserialization.');
			}

		}

		/** Errors when parsing object payload */
		class MalformedPayloadException extends PayloadParserException {

			public static function unexpectedTypeInPayload(string $type): self
			{
				return new self("Detected unexpected type in payload. Only primitive types and arrays can be in payload. '$type' given.");
			}

			public static function metadataFieldIsMissing(): self
			{
				return new self('Metadata field in payload is missing.');
			}

			public static function payloadRootMustBeAnArray(string $type): self
			{
				return new self("Payload root must be an array. '$type' given.");
			}
		}


			/** Exception related to parsing payload metadata */
			final class MalformedMetadataException extends MalformedPayloadException {
				public static function typeFieldIsMissing(): self
				{
					return new self('Type field is missing.');
				}

				public static function wrongTypeValue(): self
				{
					return new self('Wrong type field value.');
				}

				public static function missingClassName(): self
				{
					return new self('You have not provided transfer class name in metadata.');
				}

				public static function metadataMustBeAnArray(string $type): MalformedMetadataException
				{
					return new self("Metadata must be an array. '$type' given");
				}

				public static function missingSerializationVersion(): self
				{
					return new self("Missing serialization version.");
				}
			}


// -------------------------- Usage exceptions: (can be changed without BC break) --------------------------------------
final class ObjectStateException extends UsageException {

	public static function givenReferenceMustBeAnObject(string $typeGiven): self
	{
		return new self("Given reference must be an reference to a instance of class. '$typeGiven' type given.");
	}

	public static function forgottenProperty(array $propertyNames, string $class): self
	{
		$propertyNameStringList = implode(', ', $propertyNames);
		return new self("You have forgotten to serialize/deserialize following properties '$propertyNameStringList' in class '$class'. Every property must be explicitly serialized or ignored.");
	}

	public static function accessedStatePropertyThatDoesNotExists(string $offset, string $getClassName): self
	{
		return new self("You have accessed property '$offset' of state that is not available. $getClassName");
	}

	public static function notAllowedToModifyObjectState(string $class, string $offset): self
	{
		return new self("It is not allowed to modify object state. ('$class'; $offset");
	}

	public static function cannotCreateClass_classNotFound(string $className): self
	{
		return new self("Cannot create object instance. Class '$className' not found. Please check your auto-loader and class name.");
	}

	public static function mustCreateSameObjectTypeAsWasOriginalObject(string $serializedClass, string $requestedClass): self
	{
		return new self("You are creating instance of different object that was serialized. Serialized: $serializedClass; Requested: $requestedClass");
	}
}

final class ObjectStateBuilderException extends UsageException {

	public static function versionHasAlreadyBeenSet(int $setVersion, int $requestedVersion): self
	{
		return new self("State version has already been set o $setVersion. You are requesting to change it to $requestedVersion.");
	}

	public static function onlyScalarsAreAllowedAsStateFiledNames(string $type): self
	{
		return new self("Only scalars can be used for field name. '$type' given.");
	}

	/**
	 * @param int|string $name
	 * @param mixed $value
	 */
	public static function fieldIsAlreadySet($name, $value): self
	{
		if (\is_scalar($value) || (\is_object($value) && $value instanceof \Stringable)) {
			return new self(\sprintf("You have already set field '%s' to value '%s' of type '%s'.",
				$name,
				(string) $value, 
				$value instanceof \Stringable ? $value::class : 'string',
			));
		}

		return new self(\sprintf("You have already set field '%s' to value of type '%s'.",
			$name,
			\is_object($value)
				? $value::class
				: \gettype($value)
		));
	}

	/**
	 * @param int|string $fieldName
	 */
	public static function fieldHasAlreadyBeenIgnored($fieldName): self
	{
		return new self("Field '$fieldName' has been already ignored.");
	}

	public static function versionHasNotBeenProvided(): self
	{
		return new self('You have not provided serialized object version. Please do that explicilty by calling ->version() on builder.');
	}
}

final class MapperException extends UsageException {

	public static function fullyQualifiedNameCannotEndWithNamespaceSeparator(string $fullyQualifiedName): self
	{
		return new self("Fully qualified name cannot end with namespace separator. '$fullyQualifiedName' given");
	}

	public static function namespaceSeparatorMustHaveOneCharacterOnly(): self
	{
		return new self('Namespace separator must have one character only.');
	}

	public static function invalidClassNameGiven(string $className): self
	{
		return new self("Invalid class name given '$className'.");
	}
}

final class ExternalSerializerException extends UsageException {

	public static function serializerIsNotAValidFunction(\ReflectionException $previous): ExternalSerializerException
	{
		return new self('Provided (de)serializer is not a valid function.', 0, $previous);
	}

	public static function givenFunctionIsNotAValidSerializer(\ReflectionFunction $fnR): ExternalSerializerException
	{
		return new self('Given function is not valid serializer / deserializer. See docs for how it should look like. '
			. "["
				. ($fnR->getFileName() === FALSE
					? 'unknown path'
					: basename($fnR->getFileName())
				)
			. "] "
			. $fnR->getName()
			. ":"
			. $fnR->getStartLine() . "-" . $fnR->getEndLine()
		);
	}

	public static function serializerForInterfaceDoesNotMakeSense(string $parameterType): self
	{
		return new self(
			'It does not make sense to have (de)serializer for an interface or abstract class. '
			. "Provide (de)serializer for class implementations instead.\n"
			. "\n"
			. 'If you want to match all classes that implements an interface, '
			. "please read the docs on 'matching subtypes' topic. There are gotchas, read it carefully."
		);
	}
}

final class PayloadException extends UsageException
{
	public static function referencedObjectCannotBeSerialized(string $type, string $referencedBy): self
	{
		return new self("Referenced object '$type' cannot be serialized. Has been referenced by '$referencedBy';");
	}

	public static function state_cannotCreateNewInstanceWithProperties_objectWasChanged(string $className): self
	{
		return new self("It looks like you have added some properties to your object ($className) since you have serialized your object. Please use manual deserialization.");
	}
}

final class PayloadProcessorException extends UsageException {

	/** @param mixed $value */
	public static function unexpectedInputType($value): self
	{
		$typeName = gettype($value);
		return new self("Unexpected type '$typeName' on input. It looks like you have leaked '$typeName' type into 'State' produced by your object. Stateful cannot serialize '$typeName' type. If you think it should, please open an issue.");
	}

	public static function objectIsNotSerializable_noSerializerFound(string $class): self
	{
		return new self(
			  "'$class' is not serializable. Object does not implement stateful interface and no external serializer was provided for given type.\n\n"

			  . "What to do?\n"
			  . "  ⒜ implement Stateful interface on given type (or super-type)\n"
			  . "  ⒝ when ⒜ is not possible (e.g. class is in 3rd party code), provide external serializer for given type.\n"
			  . "\n"

			  . 'Please note that external serializers should match type exactly (more below). '
			  . "External serializer serializes class instances, so you should provide serializer for every class ≫implementation≪ (=for every type). \n"
			  . "\n"
			  . "\n"

			  . 'For some special cases you can use external serializers with subtypes matching. See the docs for more (section "External serializers with matching sub-types").'

		);
	}

	public static function missingNameMappingFor(string $className): self
	{
		return new self("Missing name mapping from '$className' to transfer name. Did you forget to register it in mapper?");
	}

	public static function objectStateCannotContainMetaFieldName(): self
	{
		return new self(
			  'Your object state contains meta field name (' . PayloadProcessor::META_FIELD . '). '
			. 'That is prohibited, name used internally.'
		);
	}

	public static function notAllStatePropertiesDeserialized(State $objectState): self
	{
		$className = $objectState->getClassName();
		$propertiesString = implode(', ', $objectState->getUnusedProperties());
		return new self("You have not deserialized all state properties for class '$className'. You have left '$propertiesString' unused.");
	}

	public static function unserializerDidNotCheckedObjectStateVersionNumber(string $originalClassType): self
	{
		return new self("You have provided unserializer that does not check version number of object state for an object $originalClassType. "
			. 'Add assert($state->getVersion() === 1) to your deserializer.');
	}

	public static function createdClassDoesNotMatchWithOriginalClassType(string $createdClassType, string $originalClassType): self
	{
		return new self("You have created object '$createdClassType' of different type that was original object '$originalClassType'.");
	}

	public static function onlyObjectsAndArraysCanBeConvertedToPayload(string $type): self
	{
		return new self("Only objects and arrays can be converted to payload. '$type' given.");
	}

	public static function unsupportedPayloadVersion(int $payloadVersion, ?int $expectedVersion = NULL): self
	{
		return new self(
			"Unsupported payload version $payloadVersion"
			. (
				$expectedVersion === NULL
				? '.'
				: ", expected $expectedVersion version."
			)
		);
	}
}


class ClosureExternalSerializerException extends UsageException {

	public function __construct(\ReflectionFunction $fnR, string $message, ?\Throwable $previous = NULL)
	{
		$functionLocation = $fnR->getFileName() . ':' . $fnR->getStartLine() . '-' . $fnR->getEndLine() . ' ';

		parent::__construct($functionLocation . $message, 0, $previous);
	}

	public static function wrongNumberOrArguments(\ReflectionFunction $fnR, int $actualNumber, int $requiredNumber): self
	{
		return new self($fnR, "Given serializer must have $requiredNumber of parameters. It has $actualNumber of parameters.");
	}

	public static function allowsNullToBeAnParameter(\ReflectionFunction $fnR): self
	{
		return new self($fnR, 'Serializer accepts null values in parameters.');
	}

	public static function missingReturnType(\ReflectionFunction $fnR): ClosureExternalSerializerException
	{
		return new self($fnR, 'Serializer does not have return type.');
	}

	public static function canReturnNull(\ReflectionFunction $fnR): ClosureExternalSerializerException
	{
		return new self($fnR, 'Serializer cannot return null.');
	}

	public static function doesNotSpecifyReturnType(\ReflectionFunction $fnR): ClosureExternalSerializerException
	{
		return new self($fnR, 'Serializer cannot return null.');
	}
}

final class RemovedClassesDeserializerException extends UsageException
{
	public static function deserializedValueCannotBeStateful(string $className, object $value): self
	{
		return new self(\sprintf(
			'A deserializer for removed class %s returned an instance of %s which implements the Stateful interface. '
				. 'This is disallowed to make sure that the deserialized value cannot be serialized again.',
			$className,
			\get_class($value)
		));
	}

	public static function deserializedClassExists(string $className): self
	{
		return new self(\sprintf(
			'Cannot register a deserializer for a removed class %s because the class with given name actually exists. '
				. 'Did you forget to remove the class?',
			$className
		));
	}
}

final class RemovedClassException extends RuntimeException
{
	public static function unknownProperty(string $name): self
	{
		return new self("Cannot access unknown property RemovedClass::$$name.");
	}

	public static function removedClassIsImmutable(): self
	{
		return new self('Cannot modify RemovedClass, it is immutable by design.');
	}
}
