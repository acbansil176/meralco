<?php
date_default_timezone_set("Asia/Manila");
function secToHR($seconds) {
  $hours = floor($seconds / 3600);  $minutes = floor(($seconds / 60) % 60);  $seconds = floor($seconds % 60);
  return str_pad($hours, 2, '0', STR_PAD_LEFT).":".str_pad($minutes, 2, '0', STR_PAD_LEFT).":".str_pad($seconds, 2, '0', STR_PAD_LEFT);
}
$SiteIN=[];$IntervalIn=[];$AppIn=[];$SkillIn=[];$count=[];$NoviceAnswered=[];$DevExpertAnswered=[];$ExpertAnswered=[];$Novice_Offered=[];$DevExpert_Offered=[];$Expert_Offered=[];
foreach($z->result() as $row) {
	$interval="";	
	if($freq=="Fifteen"){					
			$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
			$xtime = substr($row->{'15MinsInterval'}, -12);
			$xtime1 = date('H:i:s',strtotime($xtime));
			$xtime2 =  date('H:i:s',strtotime("+15 minutes", strtotime($xtime)));
			$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
			$interval=$xfinal;
	}elseif($freq=="Thirty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'30MinsInterval'}, -12);
				$xtime1 = date('H:i:s',strtotime($xtime));
				$xtime2 =  date('H:i:s',strtotime("+30 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
	}elseif($freq=="Sixty"){
				$xdate = date('m/d/Y', strtotime($row->{'FifteenMinuteInterval'})); 
				$xtime = substr($row->{'60MinsInterval'}, -12);
				$xtime1 = date('H:i:s',strtotime($xtime));
				$xtime2 =  date('H:i:s',strtotime("+60 minutes", strtotime($xtime)));
				$xfinal= $xdate . ' ' .$xtime1 . ' - '.$xtime2; 
				$interval=$xfinal;
	}elseif($freq=="D"){$interval=$row->perDate;
	}elseif($freq=="W"){$interval=$row->{'Sunday'}.' - ' . $row->{'Saturday'};
	}elseif($freq=="M"){$interval=$row->{'MONTH_NAME'};
	}elseif($freq=="PM"){$interval=$row->{'MONTH_NAME'};
	}elseif($freq=="Y"){$interval=$row->YEAR;
	}elseif($freq=="Morning"){if($row->Morning==''){continue;}$interval= $row->Morning;
	}elseif($freq=="Afternoon"){if($row->Afternoon==''){continue;}$interval= $row->Afternoon;
	}elseif($freq=="Graveyard"){if($row->Graveyard==''){continue;}$interval= $row->Graveyard;
	}
	if(!in_array($row->SiteName,$SiteIN)){
		array_push($SiteIN, $row->SiteName);
		$siteLoc = array_search($row->SiteName, $SiteIN);
		$AppIn[ $siteLoc ] = [];$SkillIn[ $siteLoc ] = [];
		$IntervalIn[ $siteLoc ] = [];
	}else{$siteLoc = array_search($row->SiteName, $SiteIN);}
	if(!in_array($row->Service_c,$AppIn[$siteLoc])){array_push($AppIn[$siteLoc], $row->Service_c);}
	if(!in_array($row->Skill_Desc,$SkillIn[$siteLoc])){array_push($SkillIn[$siteLoc], $row->Skill_Desc);}	
	if(!in_array($interval,$IntervalIn[$siteLoc])){array_push($IntervalIn[$siteLoc], $interval);}
	$appLoc = array_search($row->Service_c, $AppIn[$siteLoc]);
	$skiLoc = array_search($row->Skill_Desc, $SkillIn[$siteLoc]);
	$inLoc = array_search($interval, $IntervalIn[$siteLoc]);
	if(isset($count[$siteLoc][$appLoc][$skiLoc][$inLoc])){$count[$siteLoc][$appLoc][$skiLoc][$inLoc]++;
	}else{$count[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;}
	if(isset($NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){if($row->NoviceAnswered==''){}else{$NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->NoviceAnswered;}
	}else{$NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;if($row->NoviceAnswered!='' ){$NoviceAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->NoviceAnswered;}}
	if(isset($DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){if($row->DevExpertAnswered==''){}else{$DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpertAnswered;}
	}else{$DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;if($row->DevExpertAnswered!='' ){$DevExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpertAnswered;}}
	if(isset($ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc])){	if($row->ExpertAnswered==''){}else{$ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ExpertAnswered;}
	}else{$ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;if($row->ExpertAnswered!='' ){$ExpertAnswered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->ExpertAnswered;}}
	if(isset($Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc])){if($row->Novice_Offered==''){}else{$Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Novice_Offered;}
	}else{$Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;if($row->Novice_Offered!=''  ){$Novice_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Novice_Offered;}}
	if(isset($DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc])){if($row->DevExpert_Offered==''){}else{$DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpert_Offered;}	
	}else{$DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;	if($row->DevExpert_Offered!=''  ){$DevExpert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->DevExpert_Offered;}}
	if(isset($Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc])){if($row->Expert_Offered==''){}else{$Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Expert_Offered;}if($row->AnsUser_Id==''){if(  $row->Answered==""){$Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]++;}}
	}else{$Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]=0;if($row->Expert_Offered!=''  ){$Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]+=$row->Expert_Offered;}if($row->AnsUser_Id=='' ){if(  $row->Answered==""){	$Expert_Offered[$siteLoc][$appLoc][$skiLoc][$inLoc]++; }}}
}?>
<body><span id="amazing">
<style type="text/css">
		body { font-family:Helvetica !important; }
		table {  border-spacing: 0;  table-layout:fixed;}
th, td {  border-top: 0.5px solid #d7d7d7;  border-left: 0.5px solid #d7d7d7;  border-bottom: 0.5px solid #d7d7d7;  border-right: 0.5px solid #d7d7d7;  padding: 0px;}
th,td { width:10%; }
</style><br>
<div class="container-fluid" id="amazing">
<table class="table table-condensed" style="width:100%">
	<tr><th align='left' colspan="11" style="text-align:left;font-family: Helvetica; font-size:11px; border: none;">Report Code: Vhis 32</th></tr>
	<tr><th align="center" colspan="11" style="text-align:center;font-family: Helvetica; font-size:22px; border: none;">Offered vs. Answered per Application</th></tr>
	<tr ><th align="right" colspan="11" style="text-align:right;font-family: Helvetica; font-size:11px; border: none;">Report Interval:
	<?php 
	if($freq=="Morning"){echo date("m/d/Y 06:00:00 ",strtotime($df))." - ". date("m/d/Y 14:00:00",strtotime($dt));}elseif($freq=="Afternoon"){echo date("m/d/Y 14:00:00 ",strtotime($df))." - ". date("m/d/Y 22:00:00",strtotime($dt));
	}elseif($freq=="Graveyard"){	echo date("m/d/Y 22:00:00 ",strtotime($df))." - ". date("m/d/Y 06:00:00",strtotime($dt)); }elseif($freq=="Fifteen"){if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); 
	}else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:45:00",strtotime($dt)); }
	}elseif($freq=="Thirty"){if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); }else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:30:00",strtotime($dt)); }
	}elseif($freq=="Sixty"){if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); }else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:00:00",strtotime($dt)); }
	}elseif($freq=="W"){$day1 = date('w',strtotime($df));$day2 = date('w',strtotime($dt));echo date('m/d/Y H:i:s ', strtotime($df .' -'.$day1.' days'))." - ". date("m/d/Y 23:59:59" ,strtotime($dt .' +'.(6-$day2).' days'));
	}else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); 	}?></th></tr>
	</table>
<?php echo '<table class="table table-condensed"  style="font-family:Helvetica; font-size:11px;width:100%;">';
		$sumnov=0;$sumdev=0;$sumexp=0;$sumsum=0;$sumnovo=0;$sumdevo=0;$sumexpo=0;$sumsumo=0;
		foreach($SiteIN as $fsite){
			$loc = array_search($fsite, $SiteIN);
			asort($AppIn[$loc]);
			$xsite = "";
			foreach($AppIn[$loc] as $fservice){
				$appLoc = array_search($fservice, $AppIn[$loc]);
				$xservice = "";
				$xskill = "";
				asort($SkillIn[$loc]);
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);			
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0;$dev=0;$exp=0;$sum=0;$novo=0;$devo=0;$expo=0;$sumo=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);						
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;$sumo=$novo+$devo+$expo;$sumnov+=$nov;$sumdev+=$dev;$sumexp+=$exp;$sumsum+=$sum;$sumnovo+=$novo;$sumdevo+=$devo;$sumexpo+=$expo;$sumsumo+=$sumo;
		}}}}
		echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;font-weight:bold;'>
		<th colspan='2' ></th>";echo "<th >OFFERED</th>";echo "<th >ANSWERED</th>";
		echo "<th >AL</th>";echo "<th >OFFERED</th>";
		echo "<th >ANSWERED</th>";echo "<th >AL</th>";
		echo "<th >OFFERED</th>";echo "<th >ANSWERED</th>";
		echo "<th >AL</th>";echo "</tr>";
		echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#e2e2e2;'><th colspan='2'><b>Grand Total</b></th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sumnovo."</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sumnov."</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($sumnov==0? '0.00' : number_format((float)$sumnov/$sumnovo *100, 2, '.', ''))."%</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sumdevo."</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sumdev."</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($sumdev==0?  '0.00' : number_format((float)$sumdev/$sumdevo *100, 2, '.', ''))."%</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sumexpo."</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$sumexp."</th>";
		echo "<th style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($sumexp==0?  '0.00' : number_format((float)$sumexp/$sumexpo *100, 2, '.', ''))."%</th>";echo "</tr>";
		echo'<tr colspan="10" style="border:1px solid #fff !important;"><td style="border:1px solid #fff !important;"></td></tr>';	
	asort($SiteIN);
	foreach($SiteIN as $fsite){
			$loc = array_search($fsite, $SiteIN);asort($AppIn[$loc]);$xsite = "";
			if($xsite != $fsite){
				echo "<tr style='font-weight: bold;border: 1px solid #d7d7d7 ;background-color:#36CCF8'><th colspan='2'>Site Name:" . $fsite.'</th>';
				$xsite = $fsite;
			}
			$sumnov1=0;$sumdev1=0;$sumexp1=0;$sumsum1=0;$sumnovo1=0;$sumdevo1=0;$sumexpo1=0;$sumsumo1=0;
				foreach($AppIn[$loc] as $fservice){
					$appLoc = array_search($fservice, $AppIn[$loc]);asort($SkillIn[$loc]);
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0;$dev=0;$exp=0;$sum=0;$novo=0;$devo=0;$expo=0;$sumo=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;$sumo=$novo+$devo+$expo;
						$sumnov1+=$nov;$sumdev1+=$dev;$sumexp1+=$exp;$sumsum1+=$sum;$sumnovo1+=$novo;$sumdevo1+=$devo;$sumexpo1+=$expo;$sumsumo1+=$sumo;
			}}}
				echo "<th style='text-align:center'>".$sumnovo1."</th>";
				echo "<th style='text-align:center'>".$sumnov1."</th>";
				echo "<th style='text-align:center'>".($sumnov1==0? '0.00' : number_format((float)$sumnov1/$sumnovo1 *100, 2, '.', ''))."%</th>";
				echo "<th style='text-align:center'>".$sumdevo1."</th>";
				echo "<th style='text-align:center'>".$sumdev1."</th>";
				echo "<th style='text-align:center'>".($sumdev1==0?  '0.00' : number_format((float)$sumdev1/$sumdevo1 *100, 2, '.', ''))."%</th>";
				echo "<th style='text-align:center'>".$sumexpo1."</th>";
				echo "<th style='text-align:center'>".$sumexp1."</th>";
				echo "<th style='text-align:center'>".($sumexp1==0?  '0.00' : number_format((float)$sumexp1/$sumexpo1 *100, 2, '.', ''))."%</th>";echo "</tr>";foreach($AppIn[$loc] as $fservice){
			$appLoc = array_search($fservice, $AppIn[$loc]);
				$xservice = "";$xskill = "";asort($SkillIn[$loc]);
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
					$tbrow="";$sumnov=0;$sumdev=0;$sumexp=0;$sumsum=0;$sumnovo=0;$sumdevo=0;$sumexpo=0;$sumsumo=0;
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0;$dev=0;$exp=0;$sum=0;$novo=0;$devo=0;$expo=0;$sumo=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;$sumo=$novo+$devo+$expo;
						if($sumo!=0){$tbrow.= "<tr style='text-align:left;font-family: Helvetica;font-size: 11px;'>";
							$tbrow.= "<td colspan='2' style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$finterval."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$novo."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$nov."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($nov==0? '0.00' : number_format((float)$nov/$novo *100, 2, '.', ''))."%</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$devo."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$dev."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($dev==0? '0.00' : number_format((float)$dev/$devo *100, 2, '.', ''))."%</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$expo."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".$exp."</td>";
							$tbrow.= "<td style='text-align:center;font-family: Helvetica;font-size: 11px;'>".($exp==0? '0.00' : number_format((float)$exp/$expo *100, 2, '.', ''))."%</td>";$tbrow.= "</tr>";
						}
						$sumnov+=$nov;$sumdev+=$dev;$sumexp+=$exp;$sumsum+=$sum;$sumnovo+=$novo;$sumdevo+=$devo;$sumexpo+=$expo;$sumsumo+=$sumo;
				}
				if($sumsumo!=0){
				if($xservice != $fservice){
				$appLoc = array_search($fservice, $AppIn[$loc]);asort($SkillIn[$loc]);
				$sumnov1=0;$sumdev1=0;$sumexp1=0;$sumsum1=0;$sumnovo1=0;$sumdevo1=0;$sumexpo1=0;$sumsumo1=0;
				foreach($SkillIn[$loc] as $fskill){
				$skiLoc = array_search($fskill, $SkillIn[$loc]);
						foreach($IntervalIn[$loc] as $finterval){
						$nov=0;$dev=0;$exp=0;$sum=0;
						$novo=0;$devo=0;$expo=0;$sumo=0;
						$inLoc = array_search($finterval, $IntervalIn[$loc]);							
						$nov=(isset($NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $NoviceAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$dev=(isset($DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$exp=(isset($ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc]) ? $ExpertAnswered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$novo=(isset($Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Novice_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$devo=(isset($DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $DevExpert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$expo=(isset($Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc]) ? $Expert_Offered[$loc][$appLoc][$skiLoc][$inLoc] : 0);
						$sum=$nov+$dev+$exp;$sumo=$novo+$devo+$expo;$sumnov1+=$nov;$sumdev1+=$dev;$sumexp1+=$exp;$sumsum1+=$sum;$sumnovo1+=$novo;$sumdevo1+=$devo;$sumexpo1+=$expo;$sumsumo1+=$sumo;
						}
				}
				echo "<tr style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#B7EDFD;'><td colspan='2' style='text-align:center;'>Application:" . $fservice.'</td>';
				echo "<th style='text-align:center'>".$sumnovo1."</th>";
				echo "<th style='text-align:center'>".$sumnov1."</th>";
				echo "<th style='text-align:center'>".($sumnov1==0? '0.00' : number_format((float)$sumnov1/$sumnovo1 *100, 2, '.', ''))."%</th>";
				echo "<th style='text-align:center'>".$sumdevo1."</th>";
				echo "<th style='text-align:center'>".$sumdev1."</th>";
				echo "<th style='text-align:center'>".($sumdev1==0?  '0.00' : number_format((float)$sumdev1/$sumdevo1 *100, 2, '.', ''))."%</th>";
				echo "<th style='text-align:center'>".$sumexpo1."</th>";
				echo "<th style='text-align:center'>".$sumexp1."</th>";
				echo "<th style='text-align:center'>".($sumexp1==0?  '0.00' : number_format((float)$sumexp1/$sumexpo1 *100, 2, '.', ''))."%</th>";
				echo "</tr>";
				$xservice = $fservice;
				}
				if($xskill != $fskill){
				echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><td colspan='2' style='font-weight: bold;text-align:left;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'>SkillSet:" . $fskill.'</td>';
				$xskill = $fskill;
				}
				echo "<th ></th><th >NOVICE</th><th ></th>";
				echo "<th ></th><th >DEVELOPING EXPERT</th><th ></th>";
				echo "<th></th><th  width='30%'>EXPERT</th><th ></th>";echo "</tr>";
				echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#DADADA;font-weight:bold;'><th colspan='2' >Interval</th>";
				echo "<th >OFFERED</th>";echo "<th >ANSWERED</th>";
				echo "<th >AL</th>";echo "<th >OFFERED</th>";
				echo "<th >ANSWERED</th>";echo "<th >AL</th>";
				echo "<th >OFFERED</th>";echo "<th >ANSWERED</th>";
				echo "<th >AL</th>";echo "</tr>";
				echo $tbrow;
				echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'><th colspan='2'>Summary</th>";
				echo "<th>".$sumnovo."</th>";
				echo "<th>".$sumnov."</th>";
				echo "<th>".($sumnov==0? '0.00' : number_format((float)$sumnov/$sumnovo *100, 2, '.', ''))."%</th>";
				echo "<th>".$sumdevo."</th>";
				echo "<th>".$sumdev."</th>";
				echo "<th>".($sumdev==0?  '0.00' : number_format((float)$sumdev/$sumdevo *100, 2, '.', ''))."%</th>";
				echo "<th>".$sumexpo."</th>";
				echo "<th>".$sumexp."</th>";
				echo "<th>".($sumexp==0?  '0.00' : number_format((float)$sumexp/$sumexpo *100, 2, '.', ''))."%</th>";
				echo "</tr>";
}}}}
echo "</table>";	
date_default_timezone_set("Asia/Manila");
echo '<table class="table" style="display:none;color:#fff;"><tr><th></th></tr><tr><th></th></tr></table>
		<table style="display:none;color:#fff;"><tr><td>Printed By: ' . ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ' : ' . date('m/d/Y H:i:s')  . '</td></tr></table>';
echo "<span style='font-family:Helvetica;font-size:11px;'>Printed By: " .($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname')). ": " .date('m/d/Y H:i:s') . '</span>' ; ?>
<br/><br/></div></span>
<div style="float:left;padding-right:10px;" class="container">
	<div class="content" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>">	<br/><br/><h3>Export:</h3>
	<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF">
		<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank"><textarea id="pdfdata" name="html" style="display:none"></textarea><input type="hidden" name="orient" value="1"></form>
	</div>
	<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL">
	<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/excel"); ?>" method="post" target="_blank"><textarea id="exceldata" name="xhtml" style="display:none"></textarea><textarea id="excelcss" name="css" style="display:none"></textarea></form></div>
	<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV"></div>
	<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD">
	<form id="exportDOC" action="../report_controller_z/generateDOC"" method="post" target="_blank"><textarea id="docdata" name="html" style="display:none"></textarea><textarea id="htmlcss" name="css" style="display:none"></textarea></form>
	</div>
	<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">
	<form id="exportHTML" action="../report_controller_z/generateHTML" method="post" target="_blank"><textarea id="htmldata" name="html" style="display:none"></textarea></form>
	</div>
	<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post"><textarea id="exceldataserver" name="html" style="display:none"></textarea><textarea id="excelcssserver" name="css" style="display:none"></textarea></form>
	<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post"><textarea id="pdfdataserver" name="html" style="display:none"></textarea></form>
	<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post"><textarea id="docdataserver" name="html" style="display:none"></textarea><textarea id="htmlcssserver" name="css" style="display:none"></textarea></form>
	<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post"><textarea id="htmldataserver" name="html" style="display:none"></textarea></form>
	<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post"><textarea id="csvdataserver" name="html" style="display:none"></textarea></form>
	<div id="lol" style="display:none;"></div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		<?php if($this->input->post('scheduled') == true): ?>
			<?php if($this->input->post('format') == "PDF"): ?>
				$("#lol").text($("#amazing").html());
				$("#pdfdataserver").text($("#lol").text());
				setTimeout(function() {$("#exportPDFServer").submit();},500);
			<?php elseif($this->input->post('format') == "WORD"): ?>
				$("#lol").text($("#amazing").html());
				$("#docdataserver").text($("#lol").text());
				$("#htmlcssserver").text($("#css").text());
				setTimeout(function() {$("#exportDOCServer").submit();},500);
			<?php elseif($this->input->post('format') == "HTML"): ?>
				$("#lol").text($("#amazing").html());
				$("#htmldataserver").text($("#lol").text());
				setTimeout(function() {$("#exportHTMLServer").submit();},500);
			<?php elseif($this->input->post('format') == "CSV"): ?>
				var csv = [];
				var rows = document.querySelectorAll("table tr");
				for (var i = 0; i < rows.length; i++) {var row = [], cols = rows[i].querySelectorAll("td, th");				
					for (var j = 0; j < cols.length; j++)row.push(cols[j].innerText);csv.push(row.join(","));        
				}
				var finaldata = csv.join("\n");
				$("#csvdataserver").text(finaldata);
				setTimeout(function() {$("#exportCSVServer").submit();},500);
			<?php else: ?>
				$("#lol").text($("#amazing").html());
				$("#exceldataserver").text($("#lol").text());
				$("#excelcssserver").text($("#css").text());
				setTimeout(function() {$("#exportXLSServer").submit();},500);
			<?php endif; ?>
			<?php endif; ?>
});
	function exportXLS() {
		$("#lol").text($("#amazing").html());
		$("#exceldata").text($("#lol").text());
		$("#excelcss").text($("#css").text());
		setTimeout(function() {$("#exportXLS").submit();},500);
	}
	function exportPDF() {
		$("#lol").text($("#amazing").html());
		$("#pdfdata").text($("#lol").text());
		setTimeout(function() {$("#exportPDF").submit();},500);
	}
	function exportDOC() {
		$("#lol").text($("#amazing").html());
		$("#docdata").text($("#lol").text());
		$("#htmlcss").text($("#css").text());
		setTimeout(function() {$("#exportDOC").submit();},500);
	}
	function exportHTML() {
		$("#lol").text($("#amazing").html());
		$("#htmldata").text($("#lol").text());
		setTimeout(function() {$("#exportHTML").submit();},500);
	}
	function exportCSV() {
		exportTableToCSV('Report'+<?php echo date("YmdHis"); ?>+'.csv')
	}
	
	function downloadCSV(csv, filename) {
		var csvFile;
		var downloadLink;
		csvFile = new Blob([csv], {type: "text/csv"});
		downloadLink = document.createElement("a");
		downloadLink.download = filename;
		downloadLink.href = window.URL.createObjectURL(csvFile);
		downloadLink.style.display = "none";
		document.body.appendChild(downloadLink);
		downloadLink.click();
	}
function exportTableToCSV(filename) {
		var csv = [];
		var rows = document.querySelectorAll("table tr");
		for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");			
			for (var j = 0; j < cols.length; j++) {
				var str = cols[j].innerText;
				if (str.includes('`')){
				row.push('="' + cols[j].innerText.replace("`","") + '"');
				}else if (str.includes(',')){
				row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');
				}else{
				row.push('="' + cols[j].innerText + '"');
				}
			}csv.push(row.join(","));        
		}downloadCSV(csv.join("\n"), filename);
	}
</script></body>