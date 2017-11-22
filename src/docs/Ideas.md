# Versioning vs inheritance

## IDEA: layered (de)serialization

This can be a solution to inheritance problems. This makes every inheritance level to version independently.

Problems:

- more magic --> _fromState() must NOT be static
- change of deserialization lifecycle \[BC break\]
- magic: you override method and it is called by reflection on every level - WTF?

## IDEA: version per inheritance level; one serialized data

Every level would have it own version number

\[BC break\] deserialization lifecycle mismatch

## IDEA: Inheritance serialization by intentionally not supporting it

To force user to implement serialization on every level and provide its own versioning number.

This is simple and provides great control over what gets serialized. And if parent does not change API this will not break.

RESULT: serialization must be implemented in every instantiable class without using parent serialization.
ACTION needed:

- proper support of checking parent variable that must be serialized --> Not possible - leaks parent internals
- parent should be serialized over its provided API --> no properties needs to be available
- checker checks only for properties that in the most child class

To prevent parent form accidentally changing, there should exists project serialization schema. (see bellow)


# Storing object serialization schemas in project

This can make code more robust and prevents from making accidental changes.
Every change must be "committed" into serialization-schema.json in project.
