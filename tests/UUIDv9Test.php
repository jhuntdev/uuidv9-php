<?php

use PHPUnit\Framework\TestCase;
use UUIDv9\UUIDv9\UUIDv9;

class UUIDV9Test extends TestCase
{
    protected $uuidRegex;
    protected $uuidV1Regex;
    protected $uuidV4Regex;
    protected $uuidV9Regex;

    protected function setUp(): void
    {
        $this->uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $this->uuidV1Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-1[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->uuidV4Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->uuidV9Regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-9[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    }

    public function testValidateAsUuidV9()
    {
        $id1 = UUIDv9::generate();
        $id2 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4' ]);
        $id3 = UUIDv9::generate([ 'timestamp' => false ]);
        $id4 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4', 'timestamp' => false ]);

        $this->assertMatchesRegularExpression($this->uuidRegex, $id1);
        $this->assertMatchesRegularExpression($this->uuidRegex, $id2);
        $this->assertMatchesRegularExpression($this->uuidRegex, $id3);
        $this->assertMatchesRegularExpression($this->uuidRegex, $id4);
    }

    public function testGenerateSequentialIds()
    {
        $id1 = UUIDv9::generate();
        sleep(2);
        $id2 = UUIDv9::generate();
        sleep(2);
        $id3 = UUIDv9::generate();

        $this->assertLessThan($id2, $id1);
        $this->assertLessThan($id3, $id2);
    }

    public function testGenerateSequentialIdsWithPrefix()
    {
        $id1 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4' ]);
        sleep(2);
        $id2 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4' ]);
        sleep(2);
        $id3 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4' ]);

        $this->assertLessThan($id2, $id1);
        $this->assertLessThan($id3, $id2);
        $this->assertEquals('a1b2c3d4', substr($id1, 0, 8));
        $this->assertEquals('a1b2c3d4', substr($id2, 0, 8));
        $this->assertEquals('a1b2c3d4', substr($id3, 0, 8));
        $this->assertEquals(substr($id1, 9, 7), substr($id2, 9, 7));
        $this->assertEquals(substr($id2, 9, 7), substr($id3, 9, 7));
    }

    public function testGenerateNonSequentialIds()
    {
        $idS = UUIDv9::generate([ 'timestamp' => false ]);
        sleep(2);
        $idNs = UUIDv9::generate([ 'timestamp' => false ]);

        $this->assertNotEquals(substr($idS, 0, 4), substr($idNs, 0, 4));
    }

    public function testGenerateNonSequentialIdsWithPrefix()
    {
        $idS = UUIDv9::generate([ 'prefix' => 'a1b2c3d4', 'timestamp' => false ]);
        sleep(2);
        $idNs = UUIDv9::generate([ 'prefix' => 'a1b2c3d4', 'timestamp' => false ]);

        $this->assertEquals('a1b2c3d4', substr($idS, 0, 8));
        $this->assertEquals('a1b2c3d4', substr($idNs, 0, 8));
        $this->assertNotEquals(substr($idS, 9, 7), substr($idNs, 9, 7));
    }

    public function testGenerateIdsWithChecksum()
    {
        $id = UUIDv9::generate([ 'checksum' => true ]);
        $this->assertTrue(UUIDv9::verifyChecksum($id));
        $this->assertMatchesRegularExpression($this->uuidRegex, $id);
    }

    public function testGenerateIdsWithVersion()
    {
        $id = UUIDv9::generate([ 'version' => true ]);
        $this->assertMatchesRegularExpression($this->uuidV9Regex, $id);
    }

    public function testGenerateIdsWithCompatibility()
    {
        $id1 = UUIDv9::generate([ 'legacy' => true ]);
        $id2 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4', 'legacy' => true ]);
        $id3 = UUIDv9::generate([ 'timestamp' => false, 'legacy' => true ]);
        $id4 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4', 'timestamp' => false, 'legacy' => true ]);

        $this->assertMatchesRegularExpression($this->uuidV1Regex, $id1);
        $this->assertMatchesRegularExpression($this->uuidV1Regex, $id2);
        $this->assertMatchesRegularExpression($this->uuidV4Regex, $id3);
        $this->assertMatchesRegularExpression($this->uuidV4Regex, $id4);
    }

    public function testValidateAndVerifyChecksum()
    {
        $id1 = UUIDv9::generate([ 'checksum' => true ]);
        $id2 = UUIDv9::generate([ 'timestamp' => false, 'checksum' => true ]);
        $id3 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4', 'checksum' => true ]);
        $id4 = UUIDv9::generate([ 'prefix' => 'a1b2c3d4', 'timestamp' => false, 'checksum' => true, 'legacy' => true ]);
        $id5 = UUIDv9::generate([ 'checksum' => true, 'legacy' => true ]);
        $id6 = UUIDv9::generate([ 'timestamp' => false, 'checksum' => true, 'legacy' => true ]);

        $this->assertTrue(UUIDv9::isValidUUIDv9($id1, [ 'checksum' => true ]));
        $this->assertTrue(UUIDv9::isValidUUIDv9($id2, [ 'checksum' => true ]));
        $this->assertTrue(UUIDv9::isValidUUIDv9($id3, [ 'checksum' => true ]));
        $this->assertTrue(UUIDv9::isValidUUIDv9($id4, [ 'checksum' => true ]));
        $this->assertTrue(UUIDv9::isValidUUIDv9($id5, [ 'checksum' => true, 'version' => true ]));
        $this->assertTrue(UUIDv9::isValidUUIDv9($id6, [ 'checksum' => true, 'version' => true ]));
        $this->assertTrue(UUIDv9::verifyChecksum($id1));
        $this->assertTrue(UUIDv9::verifyChecksum($id2));
        $this->assertTrue(UUIDv9::verifyChecksum($id3));
        $this->assertTrue(UUIDv9::verifyChecksum($id4));
        $this->assertTrue(UUIDv9::verifyChecksum($id5));
        $this->assertTrue(UUIDv9::verifyChecksum($id6));
    }
}
