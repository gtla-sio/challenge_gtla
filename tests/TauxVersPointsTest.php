<?
// TEST UNITAIRE DE TauxVersPoint()
use PHPUnit\Framework\TestCase;

class TauxVersPointsTest extends TestCase
{
    // paliers du barème
    public function testPointsAncrage(): void
    {
        $this->assertSame(0, tauxVersPoints(0));
        $this->assertSame(250, tauxVersPoints(5));
        $this->assertSame(500, tauxVersPoints(15));
        $this->assertSame(750, tauxVersPoints(25));
        $this->assertSame(1000, tauxVersPoints(40));
    }
    
    // l'interpolation et limites
    public function testInterpolationEtLimites(): void
    {
        $this->assertSame(375, tauxVersPoints(10));
        $this->assertSame(875, tauxVersPoints(32.5));
        $this->assertSame(1000, tauxVersPoints(92));
        $this->assertSame(0, tauxVersPoints(-3));
        $this->assertSame(0, tauxVersPoints(null));
    }
}
?>
