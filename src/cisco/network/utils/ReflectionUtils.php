<?php

/*
 *     __  ___      ____  _ _    __               _
 *    /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *   / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *  / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 * /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author JoggingSplash23
 * @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\utils;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use function gc_collect_cycles;
use function gc_mem_caches;

final class ReflectionUtils
{
	private static array $propertyCache = [];
	private static array $methodCache = [];

	private function __construct() {

	}

	/**
	 * @throws ReflectionException
	 */
	public static function setProperty(string $className, object $instance, string $propertyName, mixed $value) : void
	{
		self::getCachedProperty($className, $propertyName)->setValue($instance, $value);
	}

	/**
	 * @throws ReflectionException
	 */
	private static function getCachedProperty(string $className, string $propertyName) : ReflectionProperty
	{
		$key = "$className::$propertyName";
		if (!isset(self::$propertyCache[$key])) {
			$refClass = new ReflectionClass($className);
			$refProp = $refClass->getProperty($propertyName);
			$refProp->setAccessible(true);
			self::$propertyCache[$key] = $refProp;
		}
		return self::$propertyCache[$key];
	}

	/**
	 * @throws ReflectionException
	 */
	public static function getProperty(string $className, object $instance, string $propertyName) : mixed
	{
		return self::getCachedProperty($className, $propertyName)->getValue($instance);
	}

	/**
	 * @throws ReflectionException
	 */
	public static function invokeStatic(string $className, string $methodName, mixed ...$args) : mixed
	{
		return self::getCachedMethod($className, $methodName)->invoke(null, ...$args);
	}

	/**
	 * @throws ReflectionException
	 */
	public static function invoke(string $className, object $instance, string $methodName, mixed ...$args) : mixed
	{
		return self::getCachedMethod($className, $methodName)->invoke($instance, ...$args);
	}

	/**
	 * @throws ReflectionException
	 */
	private static function getCachedMethod(string $className, string $methodName) : ReflectionMethod
	{
		$key = "$className::$methodName";
		if (!isset(self::$methodCache[$key])) {
			$refClass = new ReflectionClass($className);
			$refMeth = $refClass->getMethod($methodName);
			$refMeth->setAccessible(true);
			self::$methodCache[$key] = $refMeth;
		}
		return self::$methodCache[$key];
	}

	/**
	 * Prune properties and methods cache
	 */
	static public function pruneCache() : void {
		self::$propertyCache = [];
		self::$methodCache = [];
		gc_collect_cycles();
		gc_mem_caches();
	}

}
