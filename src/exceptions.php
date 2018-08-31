<?php declare(strict_types = 1);

namespace Grifart\Stateful\Exceptions;

use Grifart\Stateful\PayloadProcessor;
use Grifart\Stateful\State;


// library root exceptions:
abstract class RuntimeException extends \RuntimeException {}
abstract class UsageException extends \LogicException {}

// ----------- Runtime exceptions (are part of public API therefore changing them changes public API) ------------------

	class VersionMismatchException extends RuntimeException {
		public static function versionDoesNotMatch(State $state, array $supportedVersions = [])
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

			public static function cannotConvertTransferNameToClassName($transferName): self
			{
				return new self("Cannot convert transfer class name $transferName to fully qualified class name. Did you registered it into name mapper?");
			}

		}

		final class ClassNotFoundException extends PayloadParserException {

			public static function classNameDeliverFromTransferName($className, $transferName): self
			{
				return new self("Class '$className' has not been found in runtime. Class name was derived from $transferName.");
			}

		}

		final class NoAppropriateDeserializerFoundException extends PayloadParserException {

			public static function for($className): self
			{
				return new self(
					  "Cannot reconstructFromState '$className', no deserializer found. "
					. 'Object is not stateful and there are no matching external deserializers found. '
					. 'External serializer can match subtypes. Please read docs, there are some gotchas '
					. 'that you need to know about before you use this feature.'
				);
			}


			public static function unknownSerializationVersion()
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

			public static function payloadRootMustBeAnArray($type): self
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

				public static function metadataMustBeAnArray(string $type)
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
		return new self("You have forgotten to reconstructFromState properties '$propertyNameStringList' in class '$class'. Every property must be explicitly serialized or ignored.");
	}

	public static function accessedStatePropertyThatDoesNotExists(string $offset, string $getClassName): self
	{
		return new self("You have accessed property '$offset' of state that is not available. $getClassName");
	}

	public static function notAllowedToModifyObjectState(string $class, string $offset): self
	{
		return new self("It is not allowed to modify object state. ('$class'; $offset");
	}

	public static function cannotCreateClass_classNotFound($className): self
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

	public static function fieldIsAlreadySet($name, $value): self
	{
		return new self("You have already set field '$name' to value '$value'.");
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

	public static function fullyQualifiedNameCannotEndWithNamespaceSeparator($fullyQualifiedName): self
	{
		return new self("Fully qualified name cannot end with namespace separator. '$fullyQualifiedName' given");
	}

	public static function namespaceSeparatorMustHaveOneCharacterOnly(): self
	{
		return new self('Namespace separator must have one character only.');
	}
}

final class ExternalSerializerException extends UsageException {

	public static function serializerIsNotAValidFunction(\ReflectionException $previous)
	{
		return new self('Provided (de)serializer is not a valid function.', 0, $previous);
	}

	public static function givenFunctionIsNotAValidSerializer(\ReflectionFunction $fnR)
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

	public static function unexpectedObjectTypeInPayload(string $className): self
	{
		return new self("Unexpected object type '$className' in payload. Did yoy registered external serializer for this type? Shouldn't this type implement Stateful interface?");
	}

	public static function objectIsNotSerializable_noSerializerFound(string $class): self
	{
		return new self(
			  "'$class' is not serializable. Object is not stateful and no external serializer was provided for given type. "
			  . "Please note that external serializers must match type exactly.\n\n"
			  . "If you need to match subtypes you can:\n"
			  . "  - implement stateful interface --> this makes serializable automatically all subclasses (recommended)\n"
			  . '  - for external serializer use annotation for matching subtypes (read the docs; there are gotchas!)'
		);
	}

	public static function missingNameMappingFor($className): self
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

	public function __construct(\ReflectionFunction $fnR, string $message, \Throwable $previous = NULL)
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

	public static function missingReturnType(\ReflectionFunction $fnR)
	{
		return new self($fnR, 'Serializer does not have return type.');
	}

	public static function canReturnNull(\ReflectionFunction $fnR)
	{
		return new self($fnR, 'Serializer cannot return null.');
	}

	public static function doesNotSpecifyReturnType(\ReflectionFunction $fnR)
	{
		return new self($fnR, 'Serializer cannot return null.');
	}
}
