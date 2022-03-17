<?php declare(strict_types = 1);


namespace Grifart\Stateful\Integration;


use Brick\DateTime\Duration;
use Brick\DateTime\Instant;
use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalDateTime;
use Brick\DateTime\LocalTime;
use Brick\DateTime\Parser\IsoParsers;
use Brick\DateTime\TimeZone;
use Brick\DateTime\TimeZoneOffset;
use Brick\DateTime\TimeZoneRegion;
use Brick\DateTime\ZonedDateTime;
use Grifart\Stateful\ExternalSerializer\SerializerList;
use Grifart\Stateful\Mapper\CompositeMapper;
use Grifart\Stateful\Mapper\Mapper;
use Grifart\Stateful\Mapper\MatchMapper;
use Grifart\Stateful\State;

final class BrickDateTimeIntegration
{

	public static function mappers(): Mapper
	{
		return CompositeMapper::from(
			new MatchMapper(Instant::class, 'Instant'),
			new MatchMapper(LocalDate::class, 'LocalDate'),
			new MatchMapper(LocalTime::class, "LocalTime"),
			new MatchMapper(LocalDateTime::class, "LocalDateTime"),
			new MatchMapper(ZonedDateTime::class, "ZonedDateTime"),
			new MatchMapper(TimeZone::class, "TimeZone"),
			new MatchMapper(TimeZoneRegion::class, "TimeZoneRegion"),
			new MatchMapper(TimeZoneOffset::class, "TimeZoneOffset"),
			new MatchMapper(Duration::class, "TimeDuration"),
		);
	}


	public static function serializers(): SerializerList
	{
		return SerializerList::from(
			static function (Instant $instant): State {
				return new State(Instant::class, 1, [
					'timestamp' => (string) $instant
				]);
			},
			static function (State $state): Instant {
				$state->ensureVersion(1);
				/** @var array{timestamp: string} $state */
				$local = LocalDateTime::parse($state['timestamp'], IsoParsers::offsetDateTime());
				return $local->atTimeZone(TimeZone::utc())->getInstant();
			},



			static function (LocalDate $date): State {
				return new State(LocalDate::class, 1, [
					'date' => (string) $date,
				]);
			},
			static function (State $state): LocalDate {
				$state->ensureVersion(1);
				/** @var array{date: string} $state */
				return LocalDate::parse($state['date']);
			},



			static function (LocalTime $time): State {
				return new State(LocalTime::class, 1, [
					'time' => (string) $time,
				]);
			},
			static function (State $state): LocalTime {
				$state->ensureVersion(1);
				/** @var array{time: string} $state */
				return LocalTime::parse($state['time']);
			},



			static function (LocalDateTime $dateTime): State {
				return new State(LocalDateTime::class, 1, [
					'datetime' => (string) $dateTime,
				]);
			},
			static function (State $state): LocalDateTime {
				$state->ensureVersion(1);
				/** @var array{datetime: string} $state */
				return LocalDateTime::parse($state['datetime']);
			},



			static function (ZonedDateTime $dateTimeWithZone): State {
				return new State(ZonedDateTime::class, 1, [
					'dateTimeWithZone' => (string) $dateTimeWithZone,
				]);
			},
			static function (State $state): ZonedDateTime {
				$state->ensureVersion(1);
				/** @var array{dateTimeWithZone: string} $state */
				return ZonedDateTime::parse($state['dateTimeWithZone']);
			},



			/**
			 * @matchSubtypes
			 * @see explanation: https://gitlab.grifart.cz/grifart-internal/stateful/blob/master/src/docs/ExternalSerializersAndInheritance.md
			 */
			static function (TimeZone $tz): State {
				return new State(\get_class($tz), 1, [
					'id' => $tz->getId(),
				]);
			},
			/** @matchSubtypes*/
			static function (State $state): TimeZone {
				$state->ensureVersion(1);
				$desiredClassName = $state->getClassName();

				/** @var array{id: string} $state */
				$instance = TimeZone::parse($state['id']);

				// make sure that what we have serialized, we are also deserializing
				\assert($instance instanceof $desiredClassName);

				return $instance;
			},




			static function (Duration $duration): State {
				return new State(Duration::class, 1, [
					'duration' => (string) $duration,
				]);
			},
			static function (State $state): Duration {
				$state->ensureVersion(1);
				/** @var array{duration: string} $state */
				return Duration::parse($state['duration']);
			},
		);
	}

}
