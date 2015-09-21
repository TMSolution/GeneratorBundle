<?php

namespace TMSolution\GeneratorBundle\Generator\Faker;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use TMSolution\GeneratorBundle\Generator\Faker\Generator;

class ColumnTypeGuesser extends \Faker\ORM\Doctrine\ColumnTypeGuesser {

    protected $generator;

    public function __construct(Generator $generator) {
        $this->generator = $generator;
    }

    public function guessFormat($fieldName, ClassMetadata $class) {
        //echo "wybrany typ:"  . "\n";
        $generator = $this->generator;
        $type = $class->getTypeOfField($fieldName);

        

        switch ($type) {
            case 'boolean':
                return function() use ($generator) {
                    return $generator->boolean;
                };
            case 'decimal':
                $size = isset($class->fieldMappings[$fieldName]['precision']) ? $class->fieldMappings[$fieldName]['precision'] : 2;

                return function() use ($generator, $size) {
                    return $generator->randomNumber($size + 2) / 100;
                };
            case 'smallint':
                return function() {
                    return mt_rand(0, 65535);
                };
            case 'integer':
                return function() {
                    return mt_rand(0, intval('2147483647'));
                };
            case 'bigint':
                return function() {
                    return mt_rand(0, intval('18446744073709551615'));
                };
            case 'float':
                return function() {
                    return mt_rand(0, intval('4294967295')) / mt_rand(1, intval('4294967295'));
                };
            case 'array':
                return Array();    
            case 'string':
                $size = isset($class->fieldMappings[$fieldName]['length']) ? $class->fieldMappings[$fieldName]['length'] : 255;

                return function() use ($generator, $size) {
                    return $generator->text($size);
                };
            case 'text':
                return function() use ($generator) {
                    return $generator->text;
                };
            case 'datetime':
            case 'date':
            case 'time':
                return function() use ($generator) {
                    return $generator->datetime;
                };
            default:
                // //echo "null ! ".$type."\n";
                // no smart way to guess what the user expects here
                return null;
        }
    }

    public function getAssociation($fieldName, $targetEntity, $assocType, $manager) {
        $generator = $this->generator;

       return function() use ($generator, $fieldName, $targetEntity, $assocType, $manager) {

            return $generator->randomAssociation($fieldName, $targetEntity, $assocType, $manager);
       };
    }

}
