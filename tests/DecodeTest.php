<?php
/**
 * Created by PhpStorm.
 * User: Rhilip
 * Date: 2/27/2020
 * Time: 2020
 */

use Rhilip\Bencode\Bencode;
use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase;
use Rhilip\Bencode\ParseErrorException;

class DecodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @group integer
     */
    public function testDecodeInteger()
    {
        // valid values
        $this->assertEquals(213, Bencode::decode('i213e'));
        $this->assertEquals(-314, Bencode::decode('i-314e'));
        $this->assertEquals(0, Bencode::decode('i0e'));
    }

    /**
     * All encodings with a leading zero, such as i03e, are invalid,
     * other than i0e, which of course corresponds to the integer "0".
     *
     * @group integer
     */
    public function testDecodeIntegerLeadingZero()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');
        Bencode::decode('i013e');
    }

    /**
     * @group integer
     */
    public function testDecodeIntegerZeroNegative()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');

        Bencode::decode('i-013e');
    }

    /**
     * i-0e is invalid.
     *
     * @group integer
     */
    public function testDecodeIntegerMinusZero()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');
        Bencode::decode('i-0e');
    }

    /**
     * Float shouldn't pass into integer format
     *
     * @group integer
     */
    public function testDecodeIntegerFakeFloat()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');

        Bencode::decode('i2.71828e');
    }

    /**
     * String shouldn't pass into integer format
     *
     * @group integer
     */
    public function testDecodeIntegerFakeString()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');

        Bencode::decode('iffafwe');
    }

    /**
     * String shouldn't pass into integer format
     *
     * @group integer
     */
    public function testDecodeIntegerOverflow()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');

        $value = PHP_INT_MAX . '0000'; // PHP_INT_MAX * 10000
        $encoded = "i{$value}e";

        Bencode::decode($encoded);
    }

    /**
     * @group string
     */
    public function testDecodeString()
    {
        // simple string
        $this->assertEquals('String', Bencode::decode('6:String'));
        // empty string
        $this->assertEquals('', Bencode::decode('0:'));
        // special chars
        $this->assertEquals("zero\0newline\nsymblol05\x05ok", Bencode::decode("25:zero\0newline\nsymblol05\x05ok"));
        // unicode
        $this->assertEquals('日本語', Bencode::decode('9:日本語'));
    }

    /**
     * @group string
     */
    public function testDecodeStringIncorrectLengthZeroPrefix()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');

        Bencode::decode('06:String');
    }

    /**
     * @group string
     */
    public function testDecodeStringIncorrectLengthFloat()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');

        Bencode::decode('6.0:String');
    }

    /**
     * @group string
     */
    public function testDecodeStringIncorrectLengthNotNumeric()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Invalid integer format or integer overflow');

        Bencode::decode('six:String');
    }

    /**
     * @group string
     */
    public function testDecodeStringIncorrectLengthNegative()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Cannot have non-digit values for String length');

        Bencode::decode('-6:String');
    }

    /**
     * @group string
     */
    public function testDecodeStringUnexpectedEof()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('String length is not match');

        Bencode::decode('10:String');
    }

    /**
     * @group list
     */
    public function testDecodeList()
    {
        // of integers
        $this->assertEquals([2, 3, 5, 7, 11, 13], Bencode::decode('li2ei3ei5ei7ei11ei13ee'));
        // of strings
        $this->assertEquals(['s1', 's2'], Bencode::decode('l2:s12:s2e'));
        // mixed
        $this->assertEquals([2, 's1', 3, 's2', 5], Bencode::decode('li2e2:s1i3e2:s2i5ee'));
        // empty
        $this->assertEquals([], Bencode::decode('le'));
    }

    /**
     * @group dictionary
     */
    public function testDecodeDictionary()
    {
        // simple
        $this->assertEquals(['a' => 'b', 'c' => 'd'], Bencode::decode('d1:a1:b1:c1:de'));

        // numeric keys
        // php converts numeric array keys to integers
        $this->assertEquals([1 => 2, 3 => 4], Bencode::decode('d1:1i2e1:3i4ee'));

        // empty
        $this->assertEquals([], Bencode::decode('de'));
    }

    /**
     * **Notice:**
     * None Exception will throw when decode a bencode dict string with `Invalid order of dictionary keys`,
     * and a sorted PHP Array will return
     *
     * @group dictionary
     */
    public function testDecodeDictionarySorted() {
        //  d3:aaa1:b3:ccc1:de
        $this->assertEquals(['aaa' => 'b','ccc' => 'd'], Bencode::decode('d3:ccc1:d3:aaa1:be'));

        // d1:11:a2:111:b1:21:c2:221:de
        $this->assertEquals([1 => 'a', 11 => 'b', 2 => 'c', 22 => 'd'],  Bencode::decode('d1:11:a1:21:c2:111:b2:221:de'));
    }

    /**
     * @group dictionary
     */
    public function testDecodeDictionaryKeyNotString()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Non string key found in the dictionary.');

        Bencode::decode('di123ei321ee');
    }

    /**
     * @group dictionary
     */
    public function testDecodeDictionaryDuplicateKey()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Duplicate Dictionary key exist before');

        Bencode::decode('d1:a1:b1:a1:de');
    }

    /**
     * @group all
     */
    public function testDecodeNothing()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Decode Input is not valid String.');

        Bencode::decode('');
    }

    /**
     * @group all
     */
    public function testDecodeNull()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Decode Input is not valid String.');

        Bencode::decode(null);
    }

    /**
     * @group all
     */
    public function testDecodeWithExtraJunk()
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage('Could not fully decode bencode string');
        Bencode::decode('i0ejunk');
    }
}