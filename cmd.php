<?php
/**
 * Created by PhpStorm.
 * User: markk
 * Date: 2018/1/21
 * Time: 11:31
 */

//$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : '';
$cmd = $argv[1];
switch($cmd)
{
    case 'getpic':
        echo getpic();
        break;
    case 'findkid':
        echo findkid();
        break;
    case 'findtarget':
        echo findtarget();
        break;
    case 'jump':
        echo jump();
        break;
    case 'autojump':
    {
        $result=array();
        $result['error'] = 0;
        $x = 0;
        $y = 0;
        do{
            $result = autojump($x,$y);
			if(isset($result['x']))
			{
				$x = $result['x'];
				$y = $result['y'];
			}            
        }
        while($result['error'] == 0);
		echo "发生错误，请检查！\n";
        break;
    }

    case 'test':
        echo test();
        break;
}

function getpic()
{
    system("adb shell screencap -p /sdcard/autojump.png");
    //sleep(1);
    system("adb pull /sdcard/autojump.png .");
    //sleep(1);
    return '{"error":"0","state":"'.time().'"}';
}

function findkid()
{
    $jump = new Jump();
    $jump->findkid();
    //$jump->SavePic("smpic.png");
    return '{"error":"0","filename":"smpic.png","state":"'.time().'"}';
}

function findtarget()
{
    $jump = new Jump();
    $jump->findtarget();
    //$jump->SavePic("smpic.png");
    return '{"error":"0","filename":"smpic.png","state":"'.time().'"}';
}

function jump()
{
    $jump = new Jump();
    $jump->findkid();
    $jump->findtarget();
    $dist = $jump->getdist();
    //$jump->SavePic("smpic.png");
    exec('adb shell input swipe 320 410 320 410 ' . (int)($dist*5.2));
    return '{"error":"0","filename":"smpic.png","state":"'.time().'","dist":"'.$dist.'"}';
}

function autojump($pyx,$pyy)
{
    getpic();
    $jump = new Jump();

    if($pyx!=0 && $pyy!=0)
    {
        $jump->pyx = $pyx;
        $jump->pyy = $pyy;
    }

    $rgb = imagecolorat($jump->sm_pic,5,5);
    $r = ($rgb >> 16)&0xff;
    $g = ($rgb >> 8)&0xff;
    $b = $rgb&0xff;
    if($r == 51 && $g = 46 && $b = 44)
        return -1;


    $jump->findkid();
    $jump->findtarget();
    $dist = $jump->getdist();
	if(isset($argv[2]))
	if($argv[2] == "save")
	{
		$fname=time().".png";
		$jump->SavePic($fname);
	}
    


    //$xs = 5.3 + (int)((160-$dist)/10)*0.18;

    $xs = 5.15;

    if($dist<160)
        $xs = $xs + 0.05;
	if($dist<140)
        $xs = $xs + 0.1;
    if($dist<120)
        $xs = $xs + 0.05;
	if($dist<100)
        $xs = $xs + 0.15;
    if($dist<80)
        $xs = $xs + 0.2;
    if($dist<60)
        $xs = $xs + 0.3;
    if($dist<50)
        $xs = 6.50;


    $dely = (int)($dist*$xs);
	$result=array();
	if($dist>210)
	{
		$fname=time().".png";
		$jump->SavePic($fname);
		$result['error'] = -1;
		return $result;
	}
		

    echo "Dist:$dist   Xs:$xs   Delay:$dely\n";
    system('adb shell input swipe 320 410 320 410 ' . $dely);
    sleep(2);
    
    $result['x'] = $jump->pyx;
    $result['y'] = $jump->pyy;
    $result['error'] = 0;
    return $result;
}

function test()
{
    $jump = new Jump('.\1516551536.png');
//    for($i=0;$i<299;$i++)
//    {
//        $color1 = imagecolorat($jump->sm_pic,$i,241);
//        $color2 = imagecolorat($jump->sm_pic,$i+1,241);
//        $result = $jump->colordist($color1,$color2);
//        echo $i.":".$result['dist'] . "(".$result['color1'] .")" ."(".$result['color2'] .")\n";
//    }
    $jump->findkid();
    $jump->findtarget();
    $fname=time().".png";
    $jump->SavePic($fname);
}

class Jump
{
    protected $scr_pic;
    public $sm_pic;
    protected $sm_w;
    protected $sm_h;
    protected $red;
    protected $green;
    protected $blue;
    protected $color;
    protected $sx;
    protected $sy;
    protected $ex;
    protected $ey;
    public $pyx;
    public $pyy;

    public function __construct($file='.\autojump.png')
    {
        $arr = getimagesize($file);
        $pic_width = $arr[0];
        $pic_height = $arr[1];

        $this->sm_w = 300;
        $this->sm_h = (int)(300*$pic_height/$pic_width);
        $this->scr_pic = ImageCreateFromPng($file);
        $this->sm_pic = ImageCreateTrueColor($this->sm_w,$this->sm_h);
        ImageCopyResized($this->sm_pic,$this->scr_pic,0,0,0,0,$this->sm_w,$this->sm_h ,$pic_width,$pic_height);

        $this->red = imageColorAllocate($this->sm_pic,255,0,0);
        $this->green = imageColorAllocate($this->sm_pic,0,255,0);
        $this->blue = imageColorAllocate($this->sm_pic,0,255,0);
        $this->pyx=0;
        $this->pyy=0;
    }

    public function getdist()
    {
        $x1 = $this->sx;
        $y1 = $this->sy;
        $x2 = $this->ex;
        $y2 = $this->ey;
        $dist = (int)sqrt(($x1-$x2)*($x1-$x2)+($y1-$y2)*($y1-$y2));
        return $dist;
    }

    public function findkid()
    {
        $find = false;
        $tempx = 0;
        $tempy = 0;
        for($j=81;$j<$this->sm_h;$j++)
        {
            for($i=0;$i<$this->sm_w;$i++)
            {
                $rgb = imagecolorat($this->sm_pic,$i,$j);
                $r = ($rgb >> 16)&0xff;//取R
                $g = ($rgb >> 8)&0xff;//取G
                $b = $rgb&0xff;//取B
                if(($r>50&&$r<65) && ($g>50&&$g<65) && ($b>70&&$b<80))
                {
                    $this->color = $rgb;
                    $find = true;
                    $tempx = $i;
                    $tempy = $j;
                    break;
                }
                if($find)
                    break;
            }
        }
        //imagesetpixel($this->sm_pic,$tempx,$tempy,$this->red);
        echo "$tempx,$tempy\n";
        //开始找中心

        if($this->pyx==0 && $this->pyy==0)
        {
            $w = 0;
            $e = 0;
            $n = 0;
            $s = 0;
            $xc = 0;
            //find w
            $temp = $tempx;
            $js = 0;
            while($temp > 0)
            {
                $js++;
                $color1 = imagecolorat($this->sm_pic,$temp,$tempy);
                $color2 = imagecolorat($this->sm_pic,$temp-1,$tempy);
                $result = $this->colordist($color1,$color2);
                if($result['dist']>50)
                {
                    $w = $temp;
                    break;
                }
                $temp = $temp - 1;
            }
            //find e
            $temp = $tempx;
            $js = 0;
            while($temp < $this->sm_w-1)
            {
                $js++;
                $color1 = imagecolorat($this->sm_pic,$temp,$tempy);
                $color2 = imagecolorat($this->sm_pic,$temp+1,$tempy);
                $result = $this->colordist($color1,$color2);
                if($result['dist']>50)
                {
                    echo "\n".$result['dist'] . "(".$result['color1'] .")" ."(".$result['color2'] .")\n";
                    $e = $temp;
                    break;
                }

                $temp = $temp + 1;
            }

            $xc = (int)($w + ($e - $w)/2)+1;

            //find n
            $temp = $tempy;
            $js = 0;
            while($temp > 0)
            {
                $js++;
                $color1 = imagecolorat($this->sm_pic,$xc,$temp);
                $color2 = imagecolorat($this->sm_pic,$xc,$temp-1);
                $result = $this->colordist($color1,$color2);
                if($result['dist']>50)
                {
                    $n = $temp;
                    break;
                }
                $temp = $temp - 1;
            }
            //find s
            $temp = $tempy;
            $js = 0;
            while($temp < $this->sm_h-1)
            {
                $js++;
                $color1 = imagecolorat($this->sm_pic,$xc,$temp);
                $color2 = imagecolorat($this->sm_pic,$xc,$temp+1);
                $result = $this->colordist($color1,$color2);
                if($result['dist']>50 && $js>30)
                {
                    $s = $temp;
                    break;
                }
                $temp = $temp + 1;
            }
            $foot = (int)($n + ($s-$n)*0.9);
            $this->sx = $xc;
            $this->sy = $foot;
            $this->pyx = $xc - $w;
            $this->pyy = $foot-$tempy;
            echo "$w  $e  $n  $s\n";
            echo "py:".$this->pyx."-".$this->pyy." create\n";
        }
        else
        {
            //echo "py:".$this->pyx."-".$this->pyy." load\n";
            $this->sx = $tempx+$this->pyx;
            $this->sy = $tempy+$this->pyy;
        }
        imagesetpixel($this->sm_pic,$this->sx,$this->sy,$this->green);
    }

    public function findtarget()
    {
        $top = $this->findtargettop();
        $bottom = $this->findtargetbottom($top);
		echo "target top:".$top['y']."  bottom:".$bottom['y']."\n";
		if($bottom['y'] - $top['y'] < 3)
		{
			$bottom['y'] = $bottom['y']+12;
			echo "修正目标！\n";
		}
			
        $x = $top['x'];
        $y = (int)($top['y'] + ($bottom['y']-$top['y'])/2);
        //echo $top['x'].'-'.$top['y'];
        imagesetpixel($this->sm_pic,$top['x'],$top['y'],$this->red);
        imagesetpixel($this->sm_pic,$bottom['x'],$bottom['y'],$this->green);
        imagesetpixel($this->sm_pic,$x,$y,$this->blue);
        $this->ex = $x;
        $this->ey = $y;
    }

    private function findtargettop()
    {
        $temparr = array();
        $find = false;
        $topstartx = 0;
        $topstarty = 0;
        $topendx = 0;
        $topendy = 0;
        $topx = 0;
        $topy = 0;
        for($tempy=85;$tempy<$this->sm_h;$tempy++)
        {
            for($tempx=0;$tempx<$this->sm_w-1;$tempx++)
            {
                $color1 = imagecolorat($this->sm_pic,$tempx,$tempy);
                $color2 = imagecolorat($this->sm_pic,$tempx+1,$tempy);
                $result = $this->colordist($color1,$color2);
                if($result['dist']>20 && abs($tempx-$this->sx)>20)
                {
                    //echo "x:$tempx  y:$tempy   " . "(".$result['color1'].")". "(".$result['color2'].")";
                    $topstartx = $tempx+1;
                    $topstarty = $tempy;
                    $find = true;
                    break;
                }
            }
            if($find) break;
        }
        for($tempx=$topstartx;$tempx<$this->sm_w-1;$tempx++)
        {
            $color1 = imagecolorat($this->sm_pic,$tempx,$topstarty);
            $color2 = imagecolorat($this->sm_pic,$tempx+1,$topstarty);
            $result = $this->colordist($color1,$color2);
            if($result['dist']>20)
            {
                $topendx = $tempx;
                $topendy = $topstarty;
                break;
            }
        }
        $topx = (int)($topstartx+($topendx-$topstartx)/2);
        $topy = $topstarty;
        $result = array();
        $result['startx'] = $topstartx;
        $result['starty'] = $topstarty;
        $result['endx'] = $topendx;
        $result['endy'] = $topendy;
        $result['x'] = $topx;
        $result['y'] = $topy;
        $result['color'] = imagecolorat($this->sm_pic,$topx,$topy);
        //imagesetpixel($this->sm_pic,$topx,$topy,$this->green);
        return $result;
    }
    private function findtargetbottom($top)
    {
        $temparr = array();
        $topcolor = imagecolorat($this->sm_pic,$top['x'],$top['y']);
        $bottonx = $top['x'];
        $bottony = $top['y'];
        for($tempy=$top['y']+1;$tempy<$top['y']+100;$tempy++)
        {
            $color = imagecolorat($this->sm_pic,$top['x'],$tempy);
            array_push($temparr,$color);
            if($color==$topcolor || $this->colordist($color,$topcolor) < 10)
                $bottony = $tempy;
        }
        $result=array();
        $result['x'] = $bottonx;
        $result['y'] = $bottony;
        return $result;
        //imagesetpixel($this->sm_pic,$bottonx,$bottony,$this->green);
    }


    public function test()
    {
        for($x=0;$x<$this->sm_w-1;$x++)
        {
            $color1 = imagecolorat($this->sm_pic,$x,261);
            $color2 = imagecolorat($this->sm_pic,$x+1,261);
            $result = $this->colordist($color1,$color2);
            echo $x.':'.$result['color1'].'->'.$result['color1'].'='.$result['dist'].'<br>';
        }
    }

    public function colordist($color1,$color2)
    {
        $r1 = ($color1 >> 16)&0xff;
        $g1 = ($color1 >> 8)&0xff;
        $b1 = $color1&0xff;
        $r2 = ($color2 >> 16)&0xff;
        $g2 = ($color2 >> 8)&0xff;
        $b2 = $color2&0xff;
        $dist = sqrt(pow(($r1-$r2),2) + pow(($g1-$g2),2) + pow(($b1-$b2),2));
        $result=array();
        $result['dist'] = $dist;
        $result['color1'] = $r1.','.$g1.','.$b1;
        $result['color2'] = $r2.','.$g2.','.$b2;
        return $result;
    }



    public function SavePic($filename)
    {
        imagepng($this->sm_pic,"./$filename");
    }
}