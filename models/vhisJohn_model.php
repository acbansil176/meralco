<?php defined('BASEPATH') OR exit('No direct script access allowed');

class vhisJohn_model extends CI_Model {
	
	function get_vhis25_data($range,$dtFrom,$dtTo,$site,$agents,$service) {
		$cmd = "with base_om_cases as (	select * FROM REPDB.dbo.OMCases where  DATEADD(HOUR, 8, CreatedDate)  BETWEEN '".$dtFrom."' and '".$dtTo."'	)
				, base_kwatch as (select * from base_om_cases where CXE_Concern_Type__c like '%CF_Service Irregularity%')
				, base as (	SELECT ((select COUNT(*) from base_kwatch where Origin = 'Social Media')) as social,(select COUNT(*) from base_kwatch where Origin = 'Phone Call')  as voice,(SELECT COUNT(*) FROM base_kwatch where Origin = 'Email') as email,(SELECT COUNT(*) FROM base_kwatch where Origin IN ('ERC', 'In Person/Walk-in', 'Mail/Letter'))  as others), total as (SELECT (social+voice+email+others) as total from base), perc as (
				SELECT	case when CAST((select total from total) AS Float) > 0	then ((CAST((select social from base) AS float)/CAST((select total from total) AS Float))*100) 	else 0 end as social_perc,
					case when CAST((select total from total) AS Float) > 0 then ((CAST((select voice from base) AS float) / CAST((select total from total) AS Float))*100) else 0 end as voice_perc,
					case when CAST((select total from total) AS Float) > 0 then ((CAST((select email from base) AS float) / CAST((select total from total) AS Float))*100) else 0 end as email_perc,
					case when CAST((select total from total) AS Float) > 0 then ((CAST((select others from base) AS float) / CAST((select total from total) AS Float))*100) else 0 end as other_perc )
				, social_x as ( SELECT 'Social Media' as medium,(SELECT social from base) as total_count,(SELECT social_perc from perc) as percentage),	email_x as (SELECT 'Email' as medium, (SELECT email from base) as total_count,(SELECT email_perc from perc) as percentage), voice_x as (SELECT 'Voice' as medium, (SELECT voice from base) as total_count,(SELECT voice_perc from perc) as percentage), others_x as (SELECT 'Others' as medium, (SELECT others from base) as total_count,(SELECT other_perc from perc) as percentage)				
				SELECT * FROM social_x	UNION SELECT * FROM email_x	UNION SELECT * FROM voice_x	UNION SELECT * FROM others_x";
		$query = $this->db->query($cmd);
		return $query;
	}
	
	function get_vhis26_data($range,$dtFrom,$dtTo,$site,$agents,$service) {
		$cmd = "with base_phone_concern_type as (
				select*, case when CXE_Concern_Type__c IS NULL or CXE_Concern_Type__c like '' then 'Other Concerns' else CONCAT(CXE_Concern_Type__c, ' - ', CXE_Concern_Subtype__c) end as [Concern Type]
				from REPDB.dbo.OMCases where Origin in ('Phone', 'Phone Call') and DATEADD(HOUR, 8, CreatedDate) BETWEEN '".$dtFrom."' and '".$dtTo."' ), 
				base_email_concern_type as (select *, case when SCS_Case_Type__c IS NULL or SCS_Case_Type__c = '' then 'Other Concerns' else CONCAT(SCS_Case_Type__c, ' - ', SCS_Case_SubType__c) end as [Concern Type]
				from REPDB.dbo.OMCases where Record_Type__c in ('SCS_Email_Case') and DATEADD(HOUR, 8, CreatedDate) between  '".$dtFrom."' and '".$dtTo."'), 
				base_social_media_concern_type as (select *, case when SCS_Case_Type__c IS NULL or SCS_Case_Type__c = '' then 'Other Concerns' else CONCAT(SCS_Case_Type__c, ' - ', SCS_Case_SubType__c) end as [Concern Type]
				from REPDB.dbo.OMCases where Record_Type__c in ('SCS_Social_Media_Case') and DATEADD(HOUR, 8, CreatedDate) BETWEEN '".$dtFrom."' and '".$dtTo."'), 
				base_other_concern_type as (select *,  case when CXE_Concern_Type__c IS NULL or CXE_Concern_Type__c like '' then 'Other Concerns' else CONCAT(CXE_Concern_Type__c, ' - ', CXE_Concern_Type__c) end as [Concern Type]
				from REPDB.dbo.OMCases where Record_Type__c  NOT IN ('SCS_Social_Media_Case','SCS_Email_Case') and Origin not in ('Phone', 'Phone Call') and DATEADD(HOUR, 8, CreatedDate) between '".$dtFrom."' and '".$dtTo."' ), 
				concern_type_voice as (select 'VOICE' as medium, [Concern Type], count(id) as Total  from base_phone_concern_type group by [Concern Type]), concern_type_email as (select 'EMAIL' as medium, [Concern Type], count(id) as Total  from base_email_concern_type group by [Concern Type]), 
				concern_type_social_media as (select 'SOCIAL MEDIA' as medium, [Concern Type], count(id) as Total  from base_social_media_concern_type group by [Concern Type]), 
				concern_type_others as (select 'Other' as medium, [Concern Type], count(id) as Total  from base_other_concern_type group by [Concern Type]), concern_type_list as (select distinct([Concern Type]) from base_email_concern_type) select [CONCERN_TYPE] as concern, [SOCIAL MEDIA] as social_media, [VOICE] as voice, [EMAIL] as emailx, [OTHER] as other from (
				select [Concern Type] AS CONCERN_TYPE, medium, total from concern_type_voice union select [Concern Type] AS CONCERN_TYPE, medium, total from concern_type_social_media
				union select [Concern Type] AS CONCERN_TYPE, medium, total from concern_type_others  UNION select [Concern Type] AS CONCERN_TYPE, medium, total from concern_type_email) as x pivot (sum(total) for medium in( [voice],[EMAIL],[SOCIAL MEDIA],[other] )) as pw";
		$query = $this->db->query($cmd);
		return $query;
	}

	function get_vhis28_data($range,$agents,$siteid,$srvcid,$skillid,$dtFrom,$dtTo) {
		$siteid = "'" . implode ( "', '", rtrim($siteid,",") ) . "'"; $srvcid = "'" . implode ( "', '", rtrim($srvcid,",") ) . "'"; $skillid = "'" . implode ( "', '", rtrim($skillid,",") ) . "'";				
		$cmd = "with base_table_vhis28 as (select DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval,c.Service_c,dateadd(hour, 8, a.CallStartDt) as CallStartDt,a.Service_Id,a.Skill_Id,a.SeqNum,d.User_Id ,b.Skill_Desc,a.AgentSkillLevel,st.SiteName,dateadd(hour, 8, d.QueueStartDt) as QueueStartDt,dateadd(hour, 8 ,d.QueueEndDt) as QueueEndDt,dateadd(hour, 8 ,d.WrapEndDt) as WrapEndDt,d.CallActionId from REPDB.dbo.ASBRCallSkillDetail a
						left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Skills] b on a.Skill_Id = b.Skill_Id left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].service c on a.service_id = c.Service_Id left join repdb.dbo.ACDCallDetail d on a.SeqNum = d.SeqNum and a.CallId = d.CallId left join RepUIDB.dbo.Stations s on d.Station = s.Station
						left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid where a.skill_id not in (4000001,4000002) and CallTypeId = 1 and c.service_c like '%SP1%') 
					, base_agent_Count as ( select distinct dateadd(hour,8,a.callstartdt) as CallStartDt ,dateadd(hour,8,b.logindt) as LoginDt ,dateadd(hour,8,b.logoutdt) as LogoutDt ,b.User_Id as Online ,a.Service_Id ,aa.Station ,aa.ModifiedDt ,st.SiteName ,a.CallActionId ,m.Param15 ,b.User_Id ,asbr.AgentSkillLevel from repdb.dbo.ACDCallDetail a 
						left join repdb.dbo.AgentLoginLogout b on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
						left join repdb.dbo.MediaDataDetail m on b.Service_Id = m.Service_Id and dateadd(hour,8,a.callstartdt) = dateadd(hour,8,m.callstartdt) and m.SeqNum = a.SeqNum and m.CallId = a.CallId 
						left join repdb.dbo.AgentStateAudit aa on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0))  = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0)) 
						left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid and aa.Agent_Index is not null left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id
						left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.SeqNum = a.SeqNum and asbr.CallId = a.CallId and asbr.CallStartDt = a.CallStartDt where a.Service_Id IN (".$srvcid.") and service.Service_c like '%SP1%' and a.CallActionId in (5, 6, 18) and asbr.AgentSkillLevel = 0 )
					, table_2 as ( select distinct dateadd(hour,8,a.callstartdt) as CallStartDt ,dateadd(hour,8,b.logindt) as LoginDt ,dateadd(hour,8,b.logoutdt) as LogoutDt ,b.User_Id as Online ,a.Service_Id ,a.CallActionId ,m.Param15 ,st.SiteName as DNIS_SiteTagging from repdb.dbo.ACDCallDetail a
						left join  repdb.dbo.AgentLoginLogout b on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
						left join repdb.dbo.MediaDataDetail m on m.CallStartDt = a.CallStartDt left join RepUIDB.dbo.DNIS D on d.DNIS = m.Param15 left join RepUIDB.dbo.Sites st on d.SiteGuid = st.SiteGuid left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id where a.Service_Id IN (".$srvcid.") and service.Service_c like '%SP1%' and a.CallActionId in (5, 6, 18) )
					, online_agent_perStation as ( select distinct DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval ,CallStartDt ,count(distinct Online) as Count ,SiteName as StationOnline ,Service_Id ,CallActionId ,Param15 ,AgentSkillLevel from base_agent_Count group by DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) ,SiteName  ,Service_Id ,CallActionId ,Param15 ,CallStartDt ,AgentSkillLevel )
					, DNIS_tagging as ( select distinct DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval ,DNIS_SiteTagging as DNIS_Site ,Service_Id ,CallActionId ,Param15 From table_2 )
					, final_query_abandoned as ( select distinct a.FifteenMinuteInterval ,a.Count as AgentLoginCount ,a.Service_Id ,a.CallActionId ,a.StationOnline ,a.callstartdt ,b.DNIS_Site ,case when a.Count = 0 then 'MOC' when a.StationOnline != b.DNIS_Site then 'MOC' else b.DNIS_Site end as SiteName ,a.AgentSkillLevel from online_agent_perStation a left join DNIS_tagging b on a.Service_Id = b.Service_Id and a.FifteenMinuteInterval = b.FifteenMinuteInterval and a.Param15 = b.Param15 )
					, base_online_agents_perInterval as ( select FifteenMinuteInterval ,count(distinct user_id) as Online ,AgentSkillLevel ,Service_Id ,Skill_Id ,SiteName from base_table_vhis28 group by  AgentSkillLevel ,FifteenMinuteInterval ,Service_Id ,Skill_Id ,SiteName )
					, novice_online as ( select FifteenMinuteInterval ,Online as Online_Novice ,AgentSkillLevel ,Service_Id ,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 2 )
					, devExpert_online as ( select FifteenMinuteInterval ,Online as Online_DevExpert ,AgentSkillLevel ,Service_Id ,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 6 )
					, expert_online as ( select FifteenMinuteInterval ,Online as Online_Expert ,AgentSkillLevel ,Service_Id ,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 10 )
					, calls_offered_table as ( select count(distinct seqNum) as Offered ,FifteenMinuteInterval ,CallStartDt ,Service_Id ,service_c ,AgentSkillLevel ,Skill_Id ,Skill_Desc ,CallActionId ,siteName ,user_id from  base_table_vhis28 group by  FifteenMinuteInterval ,Service_Id ,AgentSkillLevel ,Skill_Id ,Skill_Desc ,CallActionId ,CallStartDt ,service_c ,siteName ,user_id)
					, expert_offered as ( select Offered ,FifteenMinuteInterval ,CallStartDt ,Service_Id ,service_c ,AgentSkillLevel ,Skill_Id ,Skill_Desc ,CallActionId ,siteName ,user_id from calls_offered_table where AgentSkillLevel IN (0,10) )
					, calls_answered_table_Group as ( select FifteenMinuteInterval ,CallStartDt ,count(distinct seqNum) as Answered ,datediff(second, queueStartDt, queueEndDt) as AnswerDelay ,datediff(second, QueueEndDt, WrapEndDt) as HandlingTime ,user_id ,QueueStartDt ,QueueEndDt ,WrapEndDt ,Service_Id ,AgentSkillLevel ,Skill_Id ,CallActionId ,service_c ,Skill_Desc ,SiteName from base_table_vhis28 where AgentSkillLevel != 0 group by  FifteenMinuteInterval ,user_id ,Service_Id ,AgentSkillLevel ,Skill_Id ,SiteName ,Skill_Desc ,CallActionId ,CallStartDt ,QueueStartDt ,QueueEndDt ,service_c ,Skill_Desc ,WrapEndDt)
					, calls_offered_group as ( select FifteenMinuteInterval ,sum(Offered) as Offered ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,siteName ,CallStartDt ,user_id from calls_offered_table group by FifteenMinuteInterval ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,siteName ,user_id ,CallStartDt )
					, answered_group as ( select FifteenMinuteInterval ,Answered ,sum(AnswerDelay) as AnswerDelay_Group ,sum(HandlingTime) as HandlingTime_Group ,sum(case when AnswerDelay <= 20 then 1 else 0 end) as WithinThreshold ,AgentSkillLevel ,user_id ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,SiteName ,CallStartDt from calls_answered_table_Group group by FifteenMinuteInterval ,AgentSkillLevel ,Service_Id ,Service_c ,SiteName ,Skill_Id ,Skill_Desc ,user_id ,Answered,CallStartDt)
					, answered_Group_With_Avg as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_Group as ASA_Group ,HandlingTime_Group as AHT_Group ,WithinThreshold ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,SiteName ,User_Id ,CallStartDt from answered_group )
					, calls_answered_with_Novice as ( select distinct FifteenMinuteInterval ,Answered ,AnswerDelay as AnswerDelay_Novice ,HandlingTime as HandlingTime_Novice ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,User_Id ,SiteName ,CallStartDt from calls_answered_table_Group where AgentSkillLevel = 2 )
					, calls_answered_with_DevExpert as ( select distinct FifteenMinuteInterval ,Answered ,AnswerDelay as AnswerDelay_DevExpert ,HandlingTime as HandlingTime_DevExpert ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,User_Id ,SiteName ,CallStartDt from calls_answered_table_Group where AgentSkillLevel = 6 )
					, calls_answered_with_Expert as ( select distinct FifteenMinuteInterval ,Answered ,AnswerDelay as AnswerDelay_Expert ,HandlingTime as HandlingTime_Expert ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,User_Id ,SiteName ,CallStartDt from calls_answered_table_Group where AgentSkillLevel = 10) 
					, novice_group as ( select FifteenMinuteInterval ,sum(Answered) as Answered  ,sum(AnswerDelay_Novice) as AnswerDelay_Novice ,sum(HandlingTime_Novice) as HandlingTime_Novice ,AgentSkillLevel ,Service_Id ,service_c ,Skill_Id ,Skill_Desc ,User_Id ,SiteName ,CallStartDt from  calls_answered_with_Novice group by FifteenMinuteInterval ,AgentSkillLevel ,Service_Id ,service_c ,Skill_Id ,Skill_Desc ,SiteName ,User_Id,CallStartDt ) 
					, novice_group_with_AVG as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_Novice as ASA_Novice ,HandlingTime_Novice as AHT_Novice ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,User_Id ,SiteName ,CallStartDt from novice_group )
					, devExpert_group as ( select  FifteenMinuteInterval ,sum(Answered) as Answered ,sum(AnswerDelay_DevExpert) as AnswerDelay_DevExpert ,sum(HandlingTime_DevExpert) as HandlingTime_DevExpert ,AgentSkillLevel ,Service_Id ,service_c ,Skill_Id ,Skill_Desc ,User_Id ,SiteName ,callstartdt from  calls_answered_with_DevExpert group by FifteenMinuteInterval ,AgentSkillLevel ,Service_Id ,service_c ,SiteName ,Skill_Id ,User_Id ,Skill_Desc ,callstartdt )
					, devExpert_group_with_AVG as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_DevExpert as ASA_DevExpert ,HandlingTime_DevExpert as AHT_DevExpert ,AgentSkillLevel ,Service_Id ,Service_c ,Skill_Id ,Skill_Desc ,User_Id ,SiteName ,callstartDt from devExpert_group )
					, expert_group as ( select FifteenMinuteInterval ,sum(Answered) as Answered ,sum(AnswerDelay_Expert) as AnswerDelay_Expert ,sum(HandlingTime_Expert) as HandlingTime_Expert ,AgentSkillLevel ,Service_Id ,service_c ,Skill_Id ,Skill_Desc ,SiteName ,User_Id ,CallStartDt from  calls_answered_with_Expert group by FifteenMinuteInterval ,AgentSkillLevel ,SiteName ,Service_Id ,service_c ,Skill_Id ,Skill_Desc ,User_Id ,CallStartDt )
					, Expert_group_with_AVG as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_Expert as ASA_Expert ,HandlingTime_Expert as AHT_Expert ,AgentSkillLevel ,Service_Id ,SiteName ,Service_c ,Skill_Id ,CallStartDt ,Skill_Desc ,User_Id from Expert_group )
					, base_AgentLogin_Count as ( select distinct dateadd(hour, 8 ,a.callstartdt) as CallStartDt ,dateadd(hour,8,b.logindt) as LoginDt ,dateadd(hour,8,b.logoutdt) as LogoutDt ,b.User_Id as Online ,a.Service_Id ,aa.Station ,aa.ModifiedDt ,st.SiteName ,a.CallActionId ,b.User_Id ,sl.Amount ,sl.Skill_Id ,sl.Level_Id ,sl.Description ,asbr.AgentSkillLevel from repdb.dbo.AgentLoginLogout b left join  repdb.dbo.ACDCallDetail a  on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt) left join repdb.dbo.AgentStateAudit aa on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0))  = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0))  and aa.Agent_Index is not null 
						left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Agent_Skills ASK on ask.User_Id = b.User_Id left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Skill_Levels SL on sl.Skill_Id = ask.Skill_Id and sl.Level_Id = ask.Level_Id left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.Service_Id = a.Service_Id and dateadd(hour,8,asbr.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt) where a.Service_Id IN (".$srvcid.") and service.Service_c like '%SP1%' and a.CallActionId in (8, 18, 5, 6) and ask.Skill_Id not in (4000001, 4000002) )
					, filtered_AgentLogin_Count as ( select  DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,Count(distinct User_Id) as Count_Login ,Service_Id ,Station ,SiteName ,CallActionId ,Amount ,Skill_Id ,Level_Id ,Description ,user_id ,agentskillLevel from base_AgentLogin_Count group by CallStartDt ,Service_Id ,Station ,SiteName ,CallActionId ,Amount ,Skill_Id ,Level_Id ,Description ,user_id ,agentskillLevel )
					, grouped_Online as ( select FifteenMinuteInterval ,count(distinct Count_Login) as Count_Login ,(case when amount = 2 then 1 end) as Online_Novice ,(case when amount = 6 then 1 end) as Online_DevExpert ,(case when amount = 10 then 1 end) as Online_Expert ,Service_Id ,SiteName ,Skill_Id ,user_id ,description ,agentskillLevel from filtered_AgentLogin_Count group by FifteenMinuteInterval ,Service_Id ,SiteName ,Skill_Id ,amount ,user_id ,Description ,agentskillLevel )
					,grouped_Online_Final as ( select  FifteenMinuteInterval ,Count(distinct Count_Login) as Count_Login ,Count(distinct Online_Novice) as Online_Novice ,Count(distinct Online_DevExpert) as Online_DevExpert ,Count(distinct Online_Expert) as Online_Expert ,user_id ,Service_Id ,SiteName ,Skill_Id ,description ,AgentSkillLevel from grouped_Online where AgentSkillLevel IN (2,6,10) group by FifteenMinuteInterval ,Service_Id  ,SiteName ,Skill_Id ,user_id ,description ,AgentSkillLevel )
					, group_Online_FInal_Abandoned as ( select distinct FifteenMinuteInterval ,Count(distinct Count_Login) as Count_Login ,Count(distinct Online_Novice) as Online_Novice ,Count(distinct Online_DevExpert) as Online_DevExpert ,Count(distinct Online_Expert) as Online_Expert ,user_id ,Service_Id ,SiteName ,Skill_Id ,description ,AgentSkillLevel from grouped_Online where agentskillLevel = 0 group by FifteenMinuteInterval ,Service_Id ,SiteName ,Skill_Id ,user_id ,description ,AgentSkillLevel )
					,base_AgentLogin_Count_with_Skill_Level as ( Select distinct dateadd(hour, 8 ,acd.CallStartDt) as CallStartDt ,acd.Service_Id ,asbr.Skill_Id ,asbr.AgentSkillLevel ,agt.User_Id ,(select top 1 level_id from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Agent_SkillsAudit aa  where aa.user_id = agt.User_Id and aa.Skill_Id = asbr.Skill_Id and aa.ModifiedDt <= acd.CallStartDt and aa.ModifiedDt <= agt.LoginDt order by ModifiedDt desc) as level_id ,aa.Station ,st.SiteName from REPDB.dbo.ACDCallDetail acd
						left join REPDB..AgentLoginLogout agt on (acd.CallStartDt between agt.LoginDt and agt.LogoutDt or acd.CallStartDt >= agt.LoginDt and agt.LogoutDt is null) left join REPDB..ASBRCallSkillDetail asbr on acd.SeqNum = asbr.SeqNum and acd.Service_Id = asbr.Service_Id
						left join repdb.dbo.AgentStateAudit aa on agt.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,agt.LoginDt)) / 1 * 1, 0))  = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0))  and aa.Agent_Index is not null left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid where acd.Service_Id IN (".$srvcid.") and asbr.Skill_Id not in (4000001, 4000002) )
					, base_table_skill_levels as ( select * from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Skill_Levels] SKL), agent_login_with_Skill_Description_Answered as ( select DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,Service_Id ,User_Id ,a.Skill_Id ,AgentSkillLevel ,a.level_id ,b.description ,a.SiteName ,a.CallStartDt from base_AgentLogin_Count_with_Skill_Level a, base_table_skill_levels b
						where AgentSkillLevel IN (2,6,10) and a.Skill_Id = b.Skill_Id and a.level_id = b.Level_Id), agent_login_with_Skill_Description_Abandoned as ( select DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,Service_Id,User_Id,a.Skill_Id,AgentSkillLevel,a.level_id,b.description,a.SiteName,a.CallStartDt from base_AgentLogin_Count_with_Skill_Level a, base_table_skill_levels b where AgentSkillLevel NOT IN (2,6,10) and  a.Skill_Id = b.Skill_Id and a.level_id = b.Level_Id  )
					, final_query as ( select  a.FifteenMinuteInterval ,a.SiteName as Site ,i.SiteName as SiteNameDNIS ,COALESCE(a.SiteName, i.siteName) as SiteName ,a.Offered ,b.Answered ,c.Answered as NoviceAnswered ,d.Answered as DevExpertAnswered ,e.Answered as ExpertAnswered ,b.WithinThreshold ,b.ASA_Group ,b.AHT_Group ,c.ASA_Novice ,c.AHT_Novice ,d.ASA_DevExpert ,d.AHT_DevExpert ,e.ASA_Expert ,e.AHT_Expert ,a.AgentSkillLevel ,a.Service_Id ,a.Service_c,a.Skill_Id ,a.Skill_Desc ,a.User_Id ,a.CallStartDt ,case when a.AgentSkillLevel = 0 then 1 else 0 end as Expert_Offered from calls_offered_table a
						left join answered_Group_With_Avg b on a.FifteenMinuteInterval = b.FifteenMinuteInterval and a.Service_Id = b.Service_Id and a.Skill_Id = b.Skill_Id and a.AgentSkillLevel = b.AgentSkillLevel and a.User_Id = b.User_Id and a.SiteName = b.SiteName and b.CallStartDt = a.CallStartDt left join novice_group_with_AVG C on a.FifteenMinuteInterval = c.FifteenMinuteInterval and a.Service_Id = c.Service_Id and a.Skill_Id = c.Skill_Id and a.AgentSkillLevel = c.AgentSkillLevel and a.User_Id = c.User_Id and a.SiteName = c.SiteName and c.CallStartDt = a.CallStartDt
						left join devExpert_group_with_AVG D on a.FifteenMinuteInterval = d.FifteenMinuteInterval and a.Service_Id = d.Service_Id and a.Skill_Id = d.Skill_Id and a.AgentSkillLevel = d.AgentSkillLevel and a.User_Id = d.User_Id and a.SiteName = d.SiteName and d.CallStartDt = a.CallStartDt left join Expert_group_with_AVG E on a.FifteenMinuteInterval = e.FifteenMinuteInterval and a.Service_Id = e.Service_Id and a.Skill_Id = e.Skill_Id and a.AgentSkillLevel = e.AgentSkillLevel and a.User_Id = e.User_Id and a.SiteName = e.SiteName and e.CallStartDt = a.CallStartDt left join  final_query_abandoned i on i.FifteenMinuteInterval = a.FifteenMinuteInterval and i.Service_Id = a.Service_Id and i.AgentSkillLevel = a.AgentSkillLevel and i.CallStartDt = a.CallStartDt )
					, finished_query as ( select CONVERT(varchar(15),CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/15*15,0) AS TIME),100) + ' - ' + CONVERT(varchar(15),dateadd(minute,15,CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/15*15,0) AS TIME)),100) AS \"15MinsInterval\"
						,CONVERT(varchar(15),CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/30*30,0) AS TIME),100) + ' - ' + CONVERT(varchar(15),dateadd(minute,30,CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/30*30,0) AS TIME)),100) AS \"30MinsInterval\"
						,CONVERT(varchar(15),CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/60*60,0) AS TIME),100) + ' - ' + CONVERT(varchar(15),dateadd(minute,60,CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/60*60,0) AS TIME)),100) AS \"60MinsInterval\"
						,CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,a.fifteenMinuteInterval))) %7 , dateadd(hour, 0,a.fifteenMinuteInterval))), 101) as Sunday ,CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,a.fifteenMinuteInterval))) %7 , dateadd(hour, 0,a.fifteenMinuteInterval))), 101) as Saturday ,datepart(wk, dateadd(hour, 0,a.fifteenMinuteInterval)) as Week_count ,datepart(w, dateadd(hour, 0,a.fifteenMinuteInterval)) as Day_Count ,datename(dw, dateadd(hour, 0,a.fifteenMinuteInterval)) as Day_Name
						,convert(varchar(10), dateadd(hour, 0,a.fifteenMinuteInterval), 101) as perDate ,DATEPART(M, DATEADD(hour, 0,a.fifteenMinuteInterval)) AS Month_Count ,cast(datename(m, dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) AS MONTH_NAME ,cast(year(dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) as Year_Month ,YEAR(DATEADD(hour, 0,a.fifteenMinuteInterval)) AS YEAR ,DATEADD(hour, 0,a.fifteenMinuteInterval) AS DATE_TIME
						,case when datepart(hour, dateadd(hour, 0, a.FifteenMinuteInterval)) <= 5 then convert(varchar(10), dateadd(DAY, -1,dateadd(hour, 0,a.FifteenMinuteInterval)), 101)  else convert(varchar(10), dateadd(hour, 0,a.FifteenMinuteInterval), 101) end + ' ' + case when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 5 or datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) >= 22 then '10PM - 6AM' when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) > = 14 and datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 21 then '2PM - 10PM' when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) >= 6 and datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 13 then '6AM - 2PM' end
						as Service_Shift ,a.CallStartDt ,a.FifteenMinuteInterval ,case when a.SiteName is null then 'MOC' else a.SiteName end as SiteName ,a.Offered ,a.Answered ,a.NoviceAnswered ,a.DevExpertAnswered ,a.ExpertAnswered ,a.WithinThreshold ,a.ASA_Group ,a.AHT_Group ,a.ASA_Novice ,a.AHT_Novice ,a.ASA_DevExpert ,a.AHT_DevExpert ,a.ASA_Expert ,a.AHT_Expert ,fla.User_Id as AnsUser_Id ,aban.User_Id as AbanUser_id ,COALESCE(fla.User_Id, aban.User_Id) as User_ID ,aban.Description as Description_aban ,fla.Description as Description_Answered ,COALESCE(fla.description, aban.Description) as Description ,a.AgentSkillLevel ,a.Service_Id ,a.Service_c ,a.Skill_Id ,a.Skill_Desc ,Expert_Offered + case when a.ExpertAnswered is null then 0 else a.ExpertAnswered end as Offered_Expert from final_query a
						left join agent_login_with_Skill_Description_Answered FLA on a.FifteenMinuteInterval = FLA.FifteenMinuteInterval and a.Skill_Id = fla.Skill_Id and a.Service_Id = fla.Service_Id and a.SiteName = fla.SiteName and a.User_Id = fla.User_Id and a.CallStartDt = fla.CallStartDt 
						left join  agent_login_with_Skill_Description_Abandoned Aban on a.FifteenMinuteInterval = Aban.FifteenMinuteInterval and a.Skill_Id = Aban.Skill_Id and a.Service_Id = Aban.Service_Id and a.SiteName = Aban.SiteName and a.AgentSkillLevel = aban.AgentSkillLevel and a.callstartdt = aban.callstartdt )
				select distinct finished_query.[15MinsInterval], finished_query.[30MinsInterval], finished_query.[60MinsInterval], finished_query.Sunday, finished_query.Saturday, finished_query.Week_count, finished_query.Day_Count, finished_query.Day_Name,  finished_query.perDate, finished_query.Month_Count, finished_query.MONTH_NAME, finished_query.Year_Month, finished_query.YEAR, finished_query.DATE_TIME, finished_query.Service_Shift, finished_query.CallStartDt, finished_query.FifteenMinuteInterval ,finished_query.SiteName, finished_query.Skill_Desc,  finished_query.Service_c as Service_Desc,  finished_query.Answered, finished_query.Offered, isnull(finished_query.Answered,0) as Answred,
					finished_query.WithinThreshold, ((isnull(finished_query.Answered,0) / finished_query.Offered) * 100) as PercentAnswerLevel, ((isnull(finished_query.WithinThreshold,0) / finished_query.Offered) * 100) as PercenServiceLevel, finished_query.ASA_Group, finished_query.AHT_Group, finished_query.NoviceAnswered, finished_query.ASA_Novice, finished_query.AHT_Novice, finished_query.DevExpertAnswered, finished_query.ASA_DevExpert, finished_query.AHT_DevExpert, finished_query.ExpertAnswered, finished_query.ASA_Expert, finished_query.AHT_Expert from finished_query 
					where Service_Id IN (".$srvcid.") and Skill_Id IN (".$skillid.") and SiteName IN (".$siteid.") and FifteenMinuteInterval >= '".$dtFrom."'  and FifteenMinuteInterval <= '".$dtTo."'";
		if($range == "Morning") { 	$cmd .= " AND Service_Shift LIKE '%6AM - 2PM' ";	 } elseif($range == "Afternoon") { 	$cmd .= " AND Service_Shift LIKE '%2PM - 10PM' "; } elseif($range == "Graveyard") { 	$cmd .= " AND Service_Shift LIKE '%10PM - 6AM' "; }
		$cmd .= "order by SiteName,Skill_Desc,finished_query.FifteenMinuteInterval";
		$query = $this->db->query($cmd);
		return $query;
	}
	
	function get_vhis31_data($range,$agents,$siteid,$srvcid,$skillid,$dtFrom,$dtTo) {
		$siteid = "'" . implode ( "', '", rtrim($siteid,",") ) . "'"; $srvcid = "'" . implode ( "', '", rtrim($srvcid,",") ) . "'"; $skillid = "'" . implode ( "', '", rtrim($skillid,",") ) . "'";
		$cmd = "with base_table_vhis28 as ( select  DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,c.Service_c,dateadd(hour, 8, a.CallStartDt) as CallStartDt ,a.Service_Id,a.Skill_Id,a.SeqNum,d.User_Id ,b.Skill_Desc,a.AgentSkillLevel,st.SiteName ,dateadd(hour, 8, d.QueueStartDt) as QueueStartDt ,dateadd(hour, 8 ,d.QueueEndDt) as QueueEndDt ,dateadd(hour, 8 ,d.WrapEndDt) as WrapEndDt ,d.CallActionId from REPDB.dbo.ASBRCallSkillDetail a left join  [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Skills] b on a.Skill_Id = b.Skill_Id left join  [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].service c on a.service_id = c.Service_Id left join repdb.dbo.ACDCallDetail d on a.SeqNum = d.SeqNum and a.CallId = d.CallId left join RepUIDB.dbo.Stations s on d.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid where a.skill_id not in (4000001,4000002) and CallTypeId = 1 )
					, base_agent_Count as ( select distinct dateadd(hour,8,a.callstartdt) as CallStartDt ,dateadd(hour,8,b.logindt) as LoginDt ,dateadd(hour,8,b.logoutdt) as LogoutDt ,b.User_Id as Online,a.Service_Id,aa.Station,aa.ModifiedDt ,st.SiteName,a.CallActionId,m.Param15,b.User_Id,asbr.AgentSkillLevel from repdb.dbo.ACDCallDetail a 
						left join repdb.dbo.AgentLoginLogout b on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt) left join repdb.dbo.MediaDataDetail m on b.Service_Id = m.Service_Id and dateadd(hour,8,a.callstartdt) = dateadd(hour,8,m.callstartdt) and m.SeqNum = a.SeqNum and m.CallId = a.CallId
						left join repdb.dbo.AgentStateAudit aa on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0))  = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0))  left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid and aa.Agent_Index is not null
						left join  [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.SeqNum = a.SeqNum and asbr.CallId = a.CallId and asbr.CallStartDt = a.CallStartDt WHERE service.Service_c like '%SP1%' ";
		if($srvcid != "") {	$cmd .= " and a.Service_Id IN (".$srvcid.") ";}
		$cmd .= " 		and a.CallActionId in (5, 6, 18) and asbr.AgentSkillLevel = 0)
					, table_2 as ( select distinct dateadd(hour,8,a.callstartdt) as CallStartDt ,dateadd(hour,8,b.logindt) as LoginDt ,dateadd(hour,8,b.logoutdt) as LogoutDt ,b.User_Id as Online,a.Service_Id,a.CallActionId ,m.Param15,st.SiteName as DNIS_SiteTagging from repdb.dbo.ACDCallDetail a
						left join repdb.dbo.AgentLoginLogout b on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt) left join repdb.dbo.MediaDataDetail m on m.CallStartDt = a.CallStartDt left join RepUIDB.dbo.DNIS D on d.DNIS = m.Param15
						left join RepUIDB.dbo.Sites st on d.SiteGuid = st.SiteGuid left join  [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id WHERE service.Service_c like '%SP1%' ";
		if($srvcid != "") { $cmd .= " and a.Service_Id IN (".$srvcid.") "; }
		$cmd .= " 		and a.CallActionId in (5, 6, 18))
					, online_agent_perStation as ( select distinct DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval ,CallStartDt,count(distinct Online) as Count,SiteName as StationOnline ,Service_Id,CallActionId,Param15,AgentSkillLevel from base_agent_Count group by DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) ,SiteName,Service_Id,CallActionId ,Param15,CallStartDt,AgentSkillLevel)
					,DNIS_tagging as ( select distinct DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval ,DNIS_SiteTagging as DNIS_Site ,Service_Id,CallActionId,Param15 From table_2)
					,final_query_abandoned as ( select distinct a.FifteenMinuteInterval ,a.Count as AgentLoginCount ,a.Service_Id,a.CallActionId,a.StationOnline ,a.callstartdt,b.DNIS_Site ,case when a.Count = 0 then 'MOC' when a.StationOnline != b.DNIS_Site then 'MOC' else b.DNIS_Site end as SiteName ,a.AgentSkillLevel from online_agent_perStation a left join  DNIS_tagging b on a.Service_Id = b.Service_Id and a.FifteenMinuteInterval = b.FifteenMinuteInterval and a.Param15 = b.Param15)
					, base_online_agents_perInterval as ( select FifteenMinuteInterval ,count(distinct user_id) as Online ,AgentSkillLevel,Service_Id,Skill_Id,SiteName from base_table_vhis28 group by  AgentSkillLevel ,FifteenMinuteInterval ,Service_Id,Skill_Id,SiteName)
					, novice_online as ( select FifteenMinuteInterval ,Online as Online_Novice ,AgentSkillLevel,Service_Id,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 2)
					, devExpert_online as ( select FifteenMinuteInterval ,Online as Online_DevExpert ,AgentSkillLevel,Service_Id,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 6)
					, expert_online as ( select FifteenMinuteInterval ,Online as Online_Expert ,AgentSkillLevel,Service_Id,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 10)
					, calls_offered_table as ( select  count(distinct seqNum) as Offered ,FifteenMinuteInterval ,CallStartDt,Service_Id,service_c,AgentSkillLevel,Skill_Id ,Skill_Desc,CallActionId,siteName,user_id from base_table_vhis28 group by  FifteenMinuteInterval ,Service_Id,AgentSkillLevel,Skill_Id ,Skill_Desc,CallActionId,CallStartDt ,service_c,siteName,user_id)
					, expert_offered as ( select Offered ,FifteenMinuteInterval ,CallStartDt,Service_Id,service_c ,AgentSkillLevel,Skill_Id,Skill_Desc ,CallActionId,siteName,user_id from calls_offered_table where AgentSkillLevel IN (0,10))
					, calls_answered_table_Group as ( select FifteenMinuteInterval ,CallStartDt ,count(distinct seqNum) as Answered ,convert(float,datediff(ms, queueStartDt, queueEndDt)) / 1000 as AnswerDelay ,datediff(second, QueueEndDt, WrapEndDt) as HandlingTime ,user_id,QueueStartDt ,QueueEndDt,WrapEndDt ,Service_Id,AgentSkillLevel ,Skill_Id,CallActionId ,service_c,Skill_Desc,SiteName from base_table_vhis28 
						where AgentSkillLevel != 0 group by  FifteenMinuteInterval ,user_id,Service_Id ,AgentSkillLevel,Skill_Id ,SiteName,Skill_Desc ,CallActionId,CallStartDt ,QueueStartDt,QueueEndDt ,service_c,Skill_Desc ,WrapEndDt)
					, calls_offered_group as ( select FifteenMinuteInterval ,sum(Offered) as Offered ,AgentSkillLevel ,Service_Id,Service_c ,Skill_Id,Skill_Desc ,siteName,CallStartDt,user_id from calls_offered_table group by FifteenMinuteInterval ,AgentSkillLevel,Service_Id ,Service_c,Skill_Id ,Skill_Desc,siteName,user_id ,CallStartDt)
					,answered_group as ( select FifteenMinuteInterval ,Answered ,sum(AnswerDelay) as AnswerDelay_Group ,sum(HandlingTime) as HandlingTime_Group ,sum(case when AnswerDelay <= 20 then 1 else 0 end) as WithinThreshold ,AgentSkillLevel,user_id ,Service_Id,Service_c ,Skill_Id,Skill_Desc ,SiteName,CallStartDt from calls_answered_table_Group group by FifteenMinuteInterval ,AgentSkillLevel,Service_Id ,Service_c,SiteName,Skill_Id ,Skill_Desc,user_id ,Answered,CallStartDt)
					, answered_Group_With_Avg as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_Group as ASA_Group ,HandlingTime_Group as AHT_Group ,WithinThreshold,AgentSkillLevel ,Service_Id,Service_c ,Skill_Id,Skill_Desc ,SiteName,User_Id,CallStartDt from answered_group)
					, calls_answered_with_Novice as ( select distinct FifteenMinuteInterval ,Answered ,AnswerDelay as AnswerDelay_Novice ,HandlingTime as HandlingTime_Novice ,AgentSkillLevel,Service_Id ,Service_c,Skill_Id,Skill_Desc ,User_Id,SiteName,CallStartDt from calls_answered_table_Group where AgentSkillLevel = 2)
					, calls_answered_with_DevExpert as ( select distinct FifteenMinuteInterval ,Answered ,AnswerDelay as AnswerDelay_DevExpert ,HandlingTime as HandlingTime_DevExpert ,AgentSkillLevel ,Service_Id,Service_c ,Skill_Id,Skill_Desc ,User_Id,SiteName,CallStartDt from calls_answered_table_Group where AgentSkillLevel = 6)
					, calls_answered_with_Expert as ( select distinct FifteenMinuteInterval ,Answered ,AnswerDelay as AnswerDelay_Expert ,HandlingTime as HandlingTime_Expert ,AgentSkillLevel,Service_Id ,Service_c,Skill_Id,Skill_Desc ,User_Id,SiteName,CallStartDt from calls_answered_table_Group where AgentSkillLevel = 10)
					,novice_group as ( select  FifteenMinuteInterval ,sum(Answered) as Answered  ,sum(AnswerDelay_Novice) as AnswerDelay_Novice ,sum(HandlingTime_Novice) as HandlingTime_Novice ,AgentSkillLevel,Service_Id ,service_c,Skill_Id,Skill_Desc ,User_Id,SiteName,CallStartDt from  calls_answered_with_Novice group by FifteenMinuteInterval ,AgentSkillLevel ,Service_Id,service_c,Skill_Id ,Skill_Desc,SiteName,User_Id ,CallStartDt)
					, novice_group_with_AVG as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_Novice as ASA_Novice ,HandlingTime_Novice as AHT_Novice ,AgentSkillLevel,Service_Id ,Service_c,Skill_Id,Skill_Desc ,User_Id,SiteName,CallStartDt from novice_group)
					, devExpert_group as ( select  FifteenMinuteInterval ,sum(Answered) as Answered ,sum(AnswerDelay_DevExpert) as AnswerDelay_DevExpert ,sum(HandlingTime_DevExpert) as HandlingTime_DevExpert ,AgentSkillLevel,Service_Id ,service_c,Skill_Id,Skill_Desc ,User_Id,SiteName,callstartdt from  calls_answered_with_DevExpert group by FifteenMinuteInterval ,AgentSkillLevel ,Service_Id,service_c,SiteName ,Skill_Id,User_Id,Skill_Desc ,callstartdt)
					, devExpert_group_with_AVG as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_DevExpert as ASA_DevExpert ,HandlingTime_DevExpert as AHT_DevExpert ,AgentSkillLevel,Service_Id ,Service_c,Skill_Id,Skill_Desc ,User_Id,SiteName,callstartDt from devExpert_group)
					, expert_group as ( select  FifteenMinuteInterval ,sum(Answered) as Answered ,sum(AnswerDelay_Expert) as AnswerDelay_Expert ,sum(HandlingTime_Expert) as HandlingTime_Expert ,AgentSkillLevel ,Service_Id,service_c,Skill_Id ,Skill_Desc, SiteName,User_Id ,CallStartDtfrom  calls_answered_with_Expert group by FifteenMinuteInterval ,AgentSkillLevel ,SiteName,Service_Id,service_c ,Skill_Id,Skill_Desc,User_Id ,CallStartDt)
					, Expert_group_with_AVG as ( select FifteenMinuteInterval ,Answered ,AnswerDelay_Expert as ASA_Expert ,HandlingTime_Expert as AHT_Expert ,AgentSkillLevel,Service_Id,SiteName,Service_c ,Skill_Id,CallStartDt,Skill_Desc,User_Id from Expert_group)
					, base_AgentLogin_Count as ( select distinct dateadd(hour, 8 ,a.callstartdt) as CallStartDt ,dateadd(hour,8,b.logindt) as LoginDt ,dateadd(hour,8,b.logoutdt) as LogoutDt ,b.User_Id as Online ,a.Service_Id,aa.Station,aa.ModifiedDt ,st.SiteName,a.CallActionId,b.User_Id,sl.Amount ,sl.Skill_Id,sl.Level_Id,sl.Description ,asbr.AgentSkillLevel from repdb.dbo.AgentLoginLogout b
						left join  repdb.dbo.ACDCallDetail a  on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt) left join repdb.dbo.AgentStateAudit aa on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0))  = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0))  and aa.Agent_Index is not null left join RepUIDB.dbo.Stations s on aa.Station = s.Station
						left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid left join  [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Agent_Skills ASK on ask.User_Id = b.User_Id left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Skill_Levels SL on sl.Skill_Id = ask.Skill_Id and sl.Level_Id = ask.Level_Id
						left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.Service_Id = a.Service_Id and dateadd(hour,8,asbr.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt) WHERE service.Service_c like '%SP1%' ";
		if($srvcid != "") { $cmd .=" and a.Service_Id IN (".$srvcid.") "; }
		$cmd .=" 		and a.CallActionId in (8, 18, 5, 6) and ask.Skill_Id not in (4000001, 4000002))
					,filtered_AgentLogin_Count as ( select DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,Count(distinct User_Id) as Count_Login,Service_Id ,Station,SiteName,CallActionId ,Amount,Skill_Id,Level_Id ,Description,user_id,agentskillLevel from base_AgentLogin_Count group by CallStartDt ,Service_Id,Station,SiteName ,CallActionId,Amount,Skill_Id ,Level_Id,Description,user_id ,agentskillLevel)
					, grouped_Online as ( select FifteenMinuteInterval ,count(distinct Count_Login) as Count_Login ,(case when amount = 2 then 1 end) as Online_Novice ,(case when amount = 6 then 1 end) as Online_DevExpert ,(case when amount = 10 then 1 end) as Online_Expert ,Service_Id,SiteName,Skill_Id ,user_id,description,agentskillLevel from  filtered_AgentLogin_Count group by FifteenMinuteInterval ,Service_Id,SiteName,Skill_Id,amount ,user_id,Description,agentskillLevel )
					,grouped_Online_Final as ( select  FifteenMinuteInterval ,Count(distinct Count_Login) as Count_Login ,Count(distinct Online_Novice) as Online_Novice ,Count(distinct Online_DevExpert) as Online_DevExpert ,Count(distinct Online_Expert) as Online_Expert ,user_id,Service_Id,SiteName ,Skill_Id,description,AgentSkillLevel from grouped_Online where AgentSkillLevel IN (2,6,10) group by FifteenMinuteInterval ,Service_Id,SiteName ,Skill_Id,user_id,description ,AgentSkillLevel)
					, group_Online_FInal_Abandoned as ( select distinct FifteenMinuteInterval ,Count(distinct Count_Login) as Count_Login ,Count(distinct Online_Novice) as Online_Novice ,Count(distinct Online_DevExpert) as Online_DevExpert ,Count(distinct Online_Expert) as Online_Expert ,user_id,Service_Id,SiteName ,Skill_Id,description,AgentSkillLevel from grouped_Online where agentskillLevel = 0 group by FifteenMinuteInterval ,Service_Id,SiteName ,Skill_Id,user_id,description ,AgentSkillLevel)
					,base_AgentLogin_Count_with_Skill_Level as ( Select distinct dateadd(hour, 8 ,acd.CallStartDt) as CallStartDt ,acd.Service_Id ,asbr.Skill_Id,asbr.AgentSkillLevel ,agt.User_Id ,(select top 1 level_id from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Agent_SkillsAudit aa  where aa.user_id = agt.User_Id and aa.Skill_Id = asbr.Skill_Id and aa.ModifiedDt <= acd.CallStartDt and aa.ModifiedDt <= agt.LoginDt order by ModifiedDt desc) as level_id ,aa.Station,st.SiteName from  REPDB.dbo.ACDCallDetail acd
						left join REPDB..AgentLoginLogout agt on (acd.CallStartDt between agt.LoginDt and agt.LogoutDt or acd.CallStartDt >= agt.LoginDt and agt.LogoutDt is null) left join REPDB..ASBRCallSkillDetail asbr on acd.SeqNum = asbr.SeqNum and acd.Service_Id = asbr.Service_Id left join repdb.dbo.AgentStateAudit aa on agt.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,agt.LoginDt)) / 1 * 1, 0))  = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0))  and aa.Agent_Index is not null
						left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid where asbr.Skill_Id not in (4000001, 4000002) ";
		if($srvcid != "") { $cmd .= " AND acd.Service_Id IN (".$srvcid.") "; }
		$cmd .= " 	), base_table_skill_levels as ( select * from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Skill_Levels] SKL)
					, agent_login_with_Skill_Description_Answered as ( select DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,Service_Id,User_Id,a.Skill_Id,AgentSkillLevel,a.level_id ,b.description,a.SiteName,a.CallStartDt from base_AgentLogin_Count_with_Skill_Level a, base_table_skill_levels b where AgentSkillLevel IN (2,6,10) and a.Skill_Id = b.Skill_Id and a.level_id = b.Level_Id)
					, agent_login_with_Skill_Description_Abandoned as ( select DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,Service_Id,User_Id,a.Skill_Id,AgentSkillLevel,a.level_id,b.description,a.SiteName,a.CallStartDt from base_AgentLogin_Count_with_Skill_Level a, base_table_skill_levels b where AgentSkillLevel NOT IN (2,6,10) and a.Skill_Id = b.Skill_Id and a.level_id = b.Level_Id)
					, final_query as ( select  a.FifteenMinuteInterval,a.SiteName as Site,i.SiteName as SiteNameDNIS ,COALESCE(a.SiteName, i.siteName) as SiteName ,a.Offered,b.Answered,c.Answered as NoviceAnswered ,d.Answered as DevExpertAnswered,e.Answered as ExpertAnswered ,b.WithinThreshold,b.ASA_Group,b.AHT_Group,c.ASA_Novice,c.AHT_Novice,d.ASA_DevExpert,d.AHT_DevExpert ,e.ASA_Expert,e.AHT_Expert,a.AgentSkillLevel,a.Service_Id,a.Service_c,a.Skill_Id,a.Skill_Desc,a.User_Id,a.CallStartDt ,case when a.AgentSkillLevel = 0 then 1 else 0 end as Expert_Offered from calls_offered_table a
						left join answered_Group_With_Avg b on a.FifteenMinuteInterval = b.FifteenMinuteInterval and a.Service_Id = b.Service_Id and a.Skill_Id = b.Skill_Id and a.AgentSkillLevel = b.AgentSkillLevel and a.User_Id = b.User_Id and a.SiteName = b.SiteName and b.CallStartDt = a.CallStartDt left join novice_group_with_AVG C on a.FifteenMinuteInterval = c.FifteenMinuteInterval and a.Service_Id = c.Service_Id and a.Skill_Id = c.Skill_Id and a.AgentSkillLevel = c.AgentSkillLevel and a.User_Id = c.User_Id and a.SiteName = c.SiteName and c.CallStartDt = a.CallStartDt
						left join devExpert_group_with_AVG D on a.FifteenMinuteInterval = d.FifteenMinuteInterval and a.Service_Id = d.Service_Id and a.Skill_Id = d.Skill_Id and a.AgentSkillLevel = d.AgentSkillLevel and a.User_Id = d.User_Id and a.SiteName = d.SiteName and d.CallStartDt = a.CallStartDt
						left join  Expert_group_with_AVG E on a.FifteenMinuteInterval = e.FifteenMinuteInterval and a.Service_Id = e.Service_Id and a.Skill_Id = e.Skill_Id and a.AgentSkillLevel = e.AgentSkillLevel and a.User_Id = e.User_Id and a.SiteName = e.SiteName and e.CallStartDt = a.CallStartDt left join  final_query_abandoned i on i.FifteenMinuteInterval = a.FifteenMinuteInterval and i.Service_Id = a.Service_Id and i.AgentSkillLevel = a.AgentSkillLevel and i.CallStartDt = a.CallStartDt)
					, finished_query as ( select CONVERT(varchar(15),CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/15*15,0) AS TIME),100) + ' - ' + CONVERT(varchar(15),dateadd(minute,15,CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/15*15,0) AS TIME)),100) AS \"15MinsInterval\",
						CONVERT(varchar(15),CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/30*30,0) AS TIME),100) + ' - ' + CONVERT(varchar(15),dateadd(minute,30,CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/30*30,0) AS TIME)),100) AS \"30MinsInterval\",
						CONVERT(varchar(15),CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/60*60,0) AS TIME),100) + ' - ' + CONVERT(varchar(15),dateadd(minute,60,CAST(dateadd(minute,datediff(minute,0,a.fifteenMinuteInterval)/60*60,0) AS TIME)),100) AS \"60MinsInterval\"
						,CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,a.fifteenMinuteInterval))) %7 , dateadd(hour, 0,a.fifteenMinuteInterval))), 101) as Sunday ,CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,a.fifteenMinuteInterval))) %7 , dateadd(hour, 0,a.fifteenMinuteInterval))), 101) as Saturday
						,datepart(wk, dateadd(hour, 0,a.fifteenMinuteInterval)) as Week_count ,datepart(w, dateadd(hour, 0,a.fifteenMinuteInterval)) as Day_Count ,datename(dw, dateadd(hour, 0,a.fifteenMinuteInterval)) as Day_Name ,convert(varchar(10), dateadd(hour, 0,a.fifteenMinuteInterval), 101) as perDate ,DATEPART(M, DATEADD(hour, 0,a.fifteenMinuteInterval)) AS Month_Count
						,cast(datename(m, dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) AS MONTH_NAME ,cast(year(dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,a.fifteenMinuteInterval)) as varchar(10)) as Year_Month ,YEAR(DATEADD(hour, 0,a.fifteenMinuteInterval)) AS YEAR ,DATEADD(hour, 0,a.fifteenMinuteInterval) AS DATE_TIME
						,case when datepart(hour, dateadd(hour, 0, a.FifteenMinuteInterval)) <= 5 then convert(varchar(10), dateadd(DAY, -1,dateadd(hour, 0,a.FifteenMinuteInterval)), 101) 
							else convert(varchar(10), dateadd(hour, 0,a.FifteenMinuteInterval), 101) end + ' ' + case when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 5 or datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) >= 22 then '10PM - 6AM' when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) > = 14 and datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 21 then '2PM - 10PM' when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) >= 6 and datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 13 then '6AM - 2PM' end as Service_Shift ,a.CallStartDt,a.FifteenMinuteInterval ,case when a.SiteName is null then 'MOC' else a.SiteName end as SiteName ,a.Offered,a.Answered,a.NoviceAnswered ,a.DevExpertAnswered
						,a.ExpertAnswered,a.WithinThreshold ,a.ASA_Group,a.AHT_Group,a.ASA_Novice,a.AHT_Novice ,a.ASA_DevExpert,a.AHT_DevExpert,a.ASA_Expert,a.AHT_Expert ,fla.User_Id as AnsUser_Id ,aban.User_Id as AbanUser_id ,COALESCE(fla.User_Id, aban.User_Id) as User_ID ,aban.Description as Description_aban ,fla.Description as Description_Answered ,COALESCE(fla.description, aban.Description) as Description ,a.AgentSkillLevel,a.Service_Id,a.Service_c ,a.Skill_Id,a.Skill_Desc ,Expert_Offered + case when a.ExpertAnswered is null then 0 else a.ExpertAnswered end as Offered_Expert from final_query a
						left join agent_login_with_Skill_Description_Answered FLA on a.FifteenMinuteInterval = FLA.FifteenMinuteInterval and a.Skill_Id = fla.Skill_Id and a.Service_Id = fla.Service_Id and a.SiteName = fla.SiteName and a.User_Id = fla.User_Id and a.CallStartDt = fla.CallStartDt left join agent_login_with_Skill_Description_Abandoned Aban on a.FifteenMinuteInterval = Aban.FifteenMinuteInterval and a.Skill_Id = Aban.Skill_Id and a.Service_Id = Aban.Service_Id and a.SiteName = Aban.SiteName and a.AgentSkillLevel = aban.AgentSkillLevel and a.callstartdt = aban.callstartdt)

				select distinct finished_query.[15MinsInterval], finished_query.[30MinsInterval], finished_query.[60MinsInterval], finished_query.Sunday, finished_query.Saturday, finished_query.Week_count, finished_query.Day_Count, finished_query.Day_Name,  finished_query.perDate, finished_query.Month_Count, finished_query.MONTH_NAME, finished_query.Year_Month, finished_query.YEAR, finished_query.DATE_TIME, finished_query.Service_Shift, finished_query.CallStartDt, finished_query.FifteenMinuteInterval ,finished_query.SiteName, finished_query.Skill_Desc, finished_query.Offered, isnull(finished_query.Answered,0) as Answred,
					finished_query.WithinThreshold, ((isnull(finished_query.Answered,0) / finished_query.Offered) * 100) as PercentAnswerLevel, ((isnull(finished_query.WithinThreshold,0) / finished_query.Offered) * 100) as PercenServiceLevel, finished_query.ASA_Group, finished_query.AHT_Group, finished_query.NoviceAnswered, finished_query.ASA_Novice, finished_query.AHT_Novice, finished_query.DevExpertAnswered, finished_query.ASA_DevExpert, finished_query.AHT_DevExpert, finished_query.ExpertAnswered, finished_query.ASA_Expert, finished_query.AHT_Expert, finished_query.Service_c as Service_Desc from finished_query 
					WHERE FifteenMinuteInterval >= '".$dtFrom."'  and FifteenMinuteInterval <= '".$dtTo."'";
		if($srvcid != "") { $cmd .= " and Service_Id IN (".$srvcid.") "; }
		if($skillid != "") { $cmd .= " and Skill_Id IN (".$skillid.") "; }
		if($siteid != "") { $cmd .= " and SiteName IN (".$siteid.") "; }
		if($range == "Morning") { $cmd .= " AND Service_Shift LIKE '%6AM - 2PM' "; } 
		elseif($range == "Afternoon") { $cmd .= " AND Service_Shift LIKE '%2PM - 10PM' "; } 
		elseif($range == "Graveyard") { $cmd .= " AND Service_Shift LIKE '%10PM - 6AM' "; }
		$cmd .=	"order by SiteName,Skill_Desc,finished_query.FifteenMinuteInterval";
		$query = $this->db->query($cmd);
		return $query;
	}

	function get_vhis20_data($range,$dtFrom,$dtTo,$site,$agents,$service) {
	
		$cmd = "
	BEGIN 
	SET NOCOUNT ON;

DECLARE @tempCradleToGrave TABLE (
	[UMID] NVARCHAR(50) NULL,
	[SeqNum] NVARCHAR(20) NULL,
	[ServiceID] nvarchar(20) NULL,
	[CallTypeID] int,
	[CallDateTime] datetime NULL,
	[CallActionId] int NULL,
	[CallActionDesc] varchar(200) NULL,
	[CallActionReasonId] int NULL,
	[CallActionReasonDesc] varchar(200) NULL,
	[CallDate] nvarchar(20) NULL,
	[CallTime] nvarchar(20) NULL,
	[EventID] float,
	[Event] nvarchar(200) NULL,
	Agent nvarchar(200) NULL,
	[Source] nvarchar(200) NULL,
	Destination nvarchar(200) NULL,
	CallData nvarchar(MAX) NULL,
	[EventData] nvarchar(200) NULL,
	[rn] float
);


DECLARE @FromDateTime DATETIME;
DECLARE @ToDateTime DATETIME;
SET @FromDateTime = '".$dtFrom."';
SET @ToDateTime = '".$dtTo."';








INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT RIGHT(DLG_UMID,22) AS UMID
	, NULL AS SeqNum
	, NULL AS ServiceID
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')) AS CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')),101) AS CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')),108) AS CallTime
	, 'CXP Inbound Call' AS [Event]
	, 0.1 as EventID
	, NULL AS Agent
	, DLG_DNIS as [Source]
	, NULL AS Destination
	, 'ANI: ' + DLG_ANI AS CallData
	, last_ds_name AS [EventData] 
	--'Script execution time: ' + CAST((SELFSERVICE_DUR_MS/1000) AS VARCHAR(10)) + ' second(s)' AS [EventData]
FROM 
[VW12PCTICXPDB01].[LOGDB].[voadmin].[VOLDDLGSTS] 
--[LOGDB].[dbo].[VOLDDLGSTS] 
WHERE NOT(DLG_UMID IS NULL)
AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime


--order by CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,UTC_TIME),'+08:00'))
;








INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'Routed to CXP IVR' as [Event]
	, 1.0 AS EventID
	, NULL as Agent
	, 'Cisco' as [Source]
	, 'DPA Agreement' as Destination
	, 'DNIS: ' + DNIS as CallData
	--,NULL as [EventData]
	, 'DPAAgreement:' + DPAAgreement  as [EventData]
FROM  
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'Routed to CXP IVR' as [Event]
	, 1.1 AS EventID
	, NULL as Agent
	, 'DPA Agreement' as [Source]
	, 'Language' as Destination
	, 'DNIS: ' + DNIS as CallData
	--,NULL as [EventData]
	, 'Language: ' + [Language]  as [EventData]
FROM  
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.12 AS EventID
	, NULL as Agent
	, 'Language' as [Source]
	, 'Main Menu' as Destination
	, 'DNIS: ' + DNIS as CallData
	--,NULL as [EventData]
	--, 'Language: ' + [Language]  as [EventData]
	, 'Main Menu: ' + MainMenu  as [EventData]
FROM  
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] <> '' AND [Language] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

--UNION
--SELECT  RIGHT(UMID,22) as UMID
--	, NULL as SeqNum
--	, NULL as ServiceID
--	, CallDate as CallDateTime
--	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
--	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
--	, 'IVR Script' as [Event]
--	, 1.2 as EventID
--	, NULL as Agent
--	, 'Main Menu' as [Source]
--	, MainMenu as Destination
--	, 'DNIS: ' + DNIS as CallData
--	--,  NULL as [EventData]
--	, 'NewAppService: ' + NewApplicationService  as [EventData]
--FROM  
--[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
----[TransactionDB].[dbo].[IVRSMenu]
--WHERE MainMenu <> '' AND [MainMenu] NOT IN ('Abandoned', 'Terminated', 'Transfer')
--AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.3 AS EventID
	, NULL as Agent
	, 'OutagesAndIncidents' as [Source]
	, OutagesAndIncidents as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [OutagesAndIncidents] <> '' AND [OutagesAndIncidents] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.4 AS EventID
	, NULL as Agent
	, 'BillingAndPayments' as [Source]
	, BillingAndPayments as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData] 
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu] 
WHERE [BillingAndPayments] <> '' AND [BillingAndPayments] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' as [Event]
	, 1.5 AS EventID
	, NULL as Agent
	, 'ProductsServicesAndPrograms' as [Source]
	, ProductsServicesAndPrograms as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData] 
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [ProductsServicesAndPrograms] <> ''  AND [ProductsServicesAndPrograms] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	, 'IVR Script' As [Event]
	, 1.6 AS EventID
	, NULL as Agent
	, 'Others' as [Source]
	, Others as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData] 
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Others] <> '' AND [Others] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	,'IVR Script' as [Event]
	, 1.7 AS EventID
	, NULL as Agent
	, 'Other Menu' as [Source]
	, OtherMenus as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [OtherMenus] <> '' AND [OtherMenus] NOT IN ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, [Language] as [Event]
	, 'IVR Script' As [Event]
	, 2.1 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'Language' as [Source]
	--, NULL as Destination
	,[Language] as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [Language] IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as Agent
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, [MainMenu] as [Event]
	, 'IVR Script' As [Event]
	, 2.2 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'Main Menu' as [Source]
	--, NULL as Destination
	, [MainMenu] as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [MainMenu] IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, OutagesAndIncidents as [Event]
	, 'IVR Script' As [Event]
	, 2.3 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'OutagesAndIncidents' as [Source]
	--, NULL as Destination
	,[OutagesAndIncidents] as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE [OutagesAndIncidents] IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, BillingAndPayments as [Event]
	, 'IVR Script' As [Event]
	, 2.4 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'BillingAndPayments' as [Source]
	--, NULL as Destination
	, BillingAndPayments as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE BillingAndPayments IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, ProductsServicesAndPrograms as [Event]
	, 'IVR Script' As [Event]
	, 2.5 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'ProductsServicesAndPrograms' as [Source]
	--, NULL as Destination
	, ProductsServicesAndPrograms as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE ProductsServicesAndPrograms IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, Others as [Event]
	, 'IVR Script' As [Event]
	, 2.6 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'Others' as [Source]
	--, NULL as Destination
	, Others as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE Others IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime

UNION
SELECT  RIGHT(UMID,22) as UMID
	, NULL as SeqNum
	, NULL as ServiceID
	, CallDate as CallDateTime
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as CallDate
	, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as CallTime
	--, OtherMenus as [Event]
	, 'IVR Script' As [Event]
	, 2.7 AS EventID
	, NULL as Agent
	--, NULL as [Source]
	, 'OtherMenus' as [Source]
	--, NULL as Destination
	, OtherMenus as Destination
	, 'DNIS: ' + DNIS as CallData
	, NULL as [EventData]
	--, 'DPAAgreement:' + DPAAgreement +'; NewAppService: ' + NewApplicationService  as [EventData]
FROM 
[VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu]
--[TransactionDB].[dbo].[IVRSMenu]
WHERE OtherMenus IN  ('Abandoned', 'Terminated', 'Transfer')
AND CallDate BETWEEN @FromDateTime AND @ToDateTime;


--IVRNODEACTIVITYDETAIL

;with CTE as (
SELECT  
	--RIGHT(MDET.Param19,22) as UMID
	 
	NULL as UMID
	, IVRN.SeqNum as SeqNum
	, IVRN.Service_Id as ServiceID
	, IVRN.NodeTypeId as CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')) as CallDateTime
	, IVRN.NodeActionID as CallActionID 
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')),108) as CallTime
	, NA.NodeActionDesc as [Event]
	--, NA.NodeTypeDesc as [Event]
	, 2.8 as EventID
	, NULL as Agent
	, NULL as [Source]
	, NULL as Destination
	, NULL as CallData
	, IVRO.Caption as EventData
	,ROW_NUMBER()OVER(Order by ivrn.seqnum) as rn
	--,ROW_NUMBER() OVER(Order by ivro.caption) as rn
	, ivro.caption as caption
	--, NA.NodeActionDesc as EventData
 FROM [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[IVRNodeActivityDetail] IVRN
 left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[IVRScriptObjects] IVRO  on IVRN.ScriptID=IVRO.ScriptId and IVRN.ObjectId=IVRO.ObjectId 
 --left join [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] MDET on IVRN.SeqNum=MDET.Seqnum and IVRN.CallId=MDET.CallId
 left join [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpNodeActions] as NA on IVRN.NodeActionID=NA.NodeActionId
WHERE
CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,IVRN.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

)


INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallDate, CallTime, [Event], 
	EventID, Agent, [Source], Destination, CallData, [EventData], rn)
select distinct base.umid, base.SeqNum, base.serviceID, base.CallTypeId, base.CallDateTime, base.CallActionID,
 base.CallDate, base.CallTime, base.Event, base.EventID, base.Agent, base.caption as [Source], nxt.caption as Destination, NULL as CallData, Null as [EventData], base.rn 
from cte base
left join cte nxt on nxt.rn=base.rn+1 and nxt.SeqNum=base.SeqNum 
--order by base.SeqNum, base.rn





--Start Insert UIP Data from Call Detail

;with CTE1 as (
SELECT RIGHT(A.UMID,22) as UMID
	, A.SeqNum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) as CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')),108) as CallTime
	, B.CallTypeDesc as [Event]
	, 3.1 as EventID
	, A.User_id as Agent
	, C.Service_c as [Source]
	, NULL as Destination
	, 'ANI:' + A.ANI as CallData
	, NULL as [EventData]
	, ROW_NUMBER()OVER(Order by A.seqnum, a.calltypeid desc) as rn
	, C.Service_c as caption
FROM 
[VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[CallDetail] as A
--[detail_epro].[dbo].[CallDetail] AS A
INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallType As B ON A.CallTypeId = B.CallTypeId
INNER JOIN 
[VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service
--config_epro.dbo.Service 
	As C ON A.Service_Id = C.Service_Id
WHERE A.CallCategoryId = 1 and A.Service_id <> 4000085
and CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime
--order by a.CallStartDt
)

INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallDate, CallTime, [Event], EventID
	, Agent, [Source], Destination, CallData, [EventData], rn)

select distinct base1.UMID, base1.SeqNum, base1.Service_Id, base1.CallTypeId, base1.CallDateTime,
 base1.CallDate, base1.CallTime, base1.Event, base1.EventID, base1.Agent, base1.Source as [Source], nxt1.caption as Destination, NULL as CallData, Null as [EventData], base1.rn 
from cte1 base1
left join cte1 nxt1 on nxt1.rn=base1.rn+1 and nxt1.SeqNum=base1.SeqNum 
order by base1.SeqNum, base1.CallDateTime, base1.rn



UPDATE A
SET A.CallActionId = B.CallActionId, 
A.CallActionReasonId = B.CallActionId, 
A.CallActionDesc = C.CallActionDesc, 
--A.CallActionReasonDesc = D.CallActionReasonDesc +  CHAR(13) + CHAR(10) + 'Queue Time: ' + 
A.CallActionReasonDesc = case B.CallActionId
							when 5 then 'Abandon Delay: ' 
							when 6 then 'Abandon Delay: ' 
							when 23 then 'Abandon Delay: ' 
							when 24 then 'Abandon Delay: ' 
							else 'Queue Time: '
						 end + 
	CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,B.QueueEndDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,B.QueueStartDt),'+08:00')),108) 
FROM @tempCradleToGrave AS A
 	INNER JOIN 
	[VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] 
	--[detail_epro].[dbo].[ACDCallDetail] 
	AS B ON B.SeqNum = A.Seqnum AND B.CallTypeId = A.CallTypeId 
	INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS C ON C.CallActionId = B.CallActionId
	INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallActionReason] AS D ON D.CallActionReasonId = B.CallActionReasonId
	where a.EventID =3.1 --and a.Event='ACD: Answered'




UPDATE @tempCradleToGrave
set [Event]='ACD: ' + CallActionDesc, [EventData]=CallActionReasonDesc
--SET [EventData] = [Event] + ': ' + CallActionDesc + '; ' + CallActionReasonDesc 
--SET [EventData] = CallActionDesc + '; ' + CallActionReasonDesc 
WHERE [Event] = 'ACD'

UPDATE A
  SET a.CallData=
	'<p>SIN:' + B.Param1 + '</p>' + 
	'<p>Caller Number: ' + B.Param2 + '</p>' +
	'<p>Callback: ' + B.Param3 + '</p>' +
	'<p>Preferred Language: ' + B.Param4 + '</p>' +
	'<p>Customer Concern: ' + B.Param5 + '</p>' +
	'<p>Date Time: ' + B.Param6 + '</p>' +
	'<p>What Time: ' + B.Param7 + '</p>' +
	'<p>Follow-up Count: ' + B.Param8 + '</p>' + 
	'<p>Skillset: ' + B.Param9 + '</p>' +
	'<p>EERT_Enabled: ' + B.Param10 + '</p>' +  
	'<p>EERT_MaxQueue: ' + B.Param11 + '</p>' +
	'<p>EERT_External: ' + B.Param12 + '</p>' +
	'<p>Trunkline: ' + B.Param13 + '</p>' +
	'<p>RepeatCount: ' + B.Param14 + '</p>' +
	'<p>Data 16: ' + B.Param16 + '</p>' +
	'<p>Segment: ' + B.Param17 + '</p>' +
	'<p>GCID: ' + B.Param18 + '</p>' +
	'<p>UMID: ' + B.Param19 + '</p>' +
	'<p>Data 20: ' + B.Param20 + '</p>'
  --A.UMID = RIGHT(B.Param19,22)
  --,	A.Destination = B.Param5 + ' - ' + B.Param9
  --,	A.CallData = CASE B.Param1
		--			WHEN NULL THEN 'ANI: ' + A.CallData 
		--			WHEN '' THEN 'ANI: ' + A.CallData 
		--			ELSE 'ANI: ' + A.CallData + CHAR(13) + CHAR(10) + 'SIN: ' + B.Param1
		--		END
  FROM @tempCradleToGrave AS A
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] AS B ON B.SeqNum = A.SeqNum
where a.EventID =3.1 and a.[Event]='ACD: Answered'

UPDATE a
	set [Event]='Transfer to External' 
	, a.CallData = '<p>ANI: ' + b.ani + '</p><p>' + 'DNIS: ' + b.dnis + '</p>'
	from @tempCradleToGrave a
	inner join [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[IVRCallDetail] b on a.seqnum=b.seqnum
	WHERE a.[Event] = 'M3' and a.Destination is null


Declare @UPTABLE TABLE(
	[UMID] NVARCHAR(50) NULL,
	--[SeqNum] NVARCHAR(20) NULL INDEX IXS NONCLUSTERED,
	[MinCallDate] datetime NULL,
	[MaxCallDate] datetime NULL,
	[Duration] Time(0) NULL
	);


insert into @UPTABLE 
 select distinct  UMID, MIN(CallDateTime) as MinCallDate, MAX(CallDateTime) as MaxCallDate
 ,CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,max(CallDateTime)),'+08:00'))-
CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,min(CallDateTime)),'+08:00')),108) as Duration
 from @tempCradleToGrave where umid in (
 select distinct umid from @tempCradleToGrave where Event in('ACD: Abandoned In Queue'))
group by UMID

update a set a.EventData='Abandon Delay: ' + convert(varchar(30), b.duration)
 from @tempCradleToGrave a inner join @UPTABLE b on a.UMID=b.UMID  and a.Event in('ACD: Abandoned In Queue')

 --End Insert UIP Data from Call Detail

 
 --Start Insert UIP Data from AgentDispoDetail
 INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallActionId, CallActionReasonId, CallDateTime,
	CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
 select NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, A.CallActionId
	, A.CallActionReasonId	
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')) as CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),108) as CallTime
	, 'Call Released: ' + D.CallActionReasonDesc as [Event]
	, 5.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, 'ANI:' + A.ANI as CallData
	, 'Handling Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.QueueEndDt),'+08:00')),108) as [EventData]
	--+ CHAR(13) + CHAR(10) + 'Agent Disp: ' + E.Disposition_desc
   FROM 
    [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS A
   --[detail_epro].[dbo].[ACDCallDetail] AS A
  INNER JOIN 
  [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service 
  --config_epro.dbo.Service 
  As B ON A.Service_Id = B.Service_Id
  INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallActionReason AS D ON A.CallActionReasonId = D.CallActionReasonId
  INNER JOIN 
   [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Disposition
  --config_epro.dbo.Disposition 
  As E ON A.AgentDispId = E.Disp_Id
  WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime
  --End Insert UIP Data from AgentDispoDetail
  
  
UPDATE A
	SET A.CallActionId = B.CallActionId, 
	A.CallActionReasonId = B.CallActionId, 
	A.CallActionDesc = C.CallActionDesc, A.CallActionReasonDesc = D.CallActionReasonDesc
	FROM @tempCradleToGrave AS A
 		INNER JOIN 
		[VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail]
		--[detail_epro].[dbo].[ACDCallDetail] 
		AS B ON B.SeqNum = A.Seqnum AND B.CallTypeId = A.CallTypeId 
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS C ON C.CallActionId = B.CallActionId
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallActionReason] AS D ON D.CallActionReasonId = B.CallActionReasonId
		where a.EventID =4.1


--Start Hold Calls
INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId, 
CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT  NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')) as CallDateTime
	, NULL as CallActionID
	, NULL as CallActionReasonID
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),108) as CallTime
	, 'Call On Hold' as [Event]
	, 4.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, NULL as CallData
	
	, 'Hold Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldEndDt),'+08:00'))-
		CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.HoldStartDt),'+08:00')),108) as [EventData]
   FROM 
    [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[OnCallActivityDetail]  AS A
   --[detail_epro].[dbo].[OnCallActivityDetail] AS A
  WHERE A.CallTypeId = 1
  AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime


--End Hold Calls




--End Hold Calls
--Start Consultaion
INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallDateTime, CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT  NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')) as CallDateTime
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),108) as Calltime
	, 'Consultation' as Event
	, 4.2 as EvetnID
	, A.User_Id as Agent
	, A.FirstPartyId as [Source]
	, A.SecondPartyId as Destination
	, B.CallActionDesc as CallData
	
	, 'Consultation Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00'))-
		CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,ISNULL(A.ConnectDt,A.ConnClearDt)),'+08:00')),108) as [EventData]
	--,'Consultation Duration: ' + CAST(DATEDIFF(second,ISNULL(A.ConnectDt,A.ConnClearDt),A.ConnClearDt) AS nvarchar) as [EventData]
	FROM [REPDB].[dbo].[ConsultationCallDetail] as A
	LEFT JOIN [VW12PCTIDB01\UIP_CONFIG].[lookup].[dbo].[tlkpCallAction] AS B ON A.CallActionId = B.CallActionId 
  WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

  
update a set a.Source=b.UserFullName
 from @tempCradleToGrave a inner join 
 [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users
 --[config_epro].[dbo].[Users] 
 b on a.Source=b.User_Id and a.EventID=4.2

 update a set a.Destination=b.UserFullName
 from @tempCradleToGrave a inner join 
 [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users
 --[config_epro].[dbo].[Users] 
 b on a.Destination=b.User_Id and a.EventID=4.2

--end consultaion
--Manual Call on Wrap State
 INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId, 
	CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
SELECT NULL as UMID
	,A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')) as CallDateTime
	, NULL as CallActionID
	, NULL as CallActionReasonID
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),108) as CallTime
	, 'Manual Call During Wrap' as [Event]
	, 6.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, NULL as CallData
	, 'Handling Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallEndDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapCallStartDt),'+08:00')),108) as [EventData]
   FROM 
   [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[OnCallActivityDetail]  AS A
   --[detail_epro].[dbo].[OnCallActivityDetail] AS A
  WHERE A.CallTypeId IN (9) AND CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime


--start insert wrapup time
  INSERT INTO @tempCradleToGrave (UMID, SeqNum, ServiceID, CallTypeId, CallDateTime, CallActionId, CallActionReasonId, 
	CallDate, CallTime, [Event], EventID, Agent, [Source], Destination, CallData, [EventData])
  SELECT NULL as UMID
	, A.Seqnum
	, A.Service_Id
	, A.CallTypeId
	, CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')) as CallDateTime
	, A.CallActionId
	, A.CallActionReasonId
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')),101) as CallDate
	, CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00')),108) as CallTime
	, 'Wrapup' --'Agent Disp: ' + E.Disposition_desc, --+ D.CallActionReasonDesc,  
	, 7.1 as EventID
	, A.User_Id as Agent
	, NULL as [Source]
	, NULL as Destination
	, 'ANI: ' + A.ANI as CallData
	, 'Wrapup Time: ' + CONVERT(NVARCHAR,CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.WrapEndDt),'+08:00'))-
	CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.ConnClearDt),'+08:00')),108) + '
' + '
' + 'Agent Disp: ' + E.Disposition_desc
   FROM 
    [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[ACDCallDetail] AS A
   --[detail_epro].[dbo].[ACDCallDetail] AS A
  INNER JOIN 
  [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Service
  --config_epro.dbo.Service 
  As B ON A.Service_Id = B.Service_Id
  INNER JOIN [VW12PCTIDB01\UIP_CONFIG].lookup.dbo.tlkpCallActionReason AS D ON A.CallActionReasonId = D.CallActionReasonId
  INNER JOIN 
  [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Disposition
  --config_epro.dbo.Disposition 
  As E ON A.AgentDispId = E.Disp_Id
  WHERE CONVERT(DATETIME, SWITCHOFFSET(CONVERT(datetimeoffset,A.CallStartDt),'+08:00')) BETWEEN @FromDateTime AND @ToDateTime

  --end wrapup time

  


update a set a.Agent=b.UserFullName
 from @tempCradleToGrave a inner join 
 [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.Users
 --[config_epro].[dbo].[Users] 
 b on a.Agent=b.User_Id and a.Agent is not null  


UPDATE A
  SET A.UMID = RIGHT(B.Param19,22)
  --,	A.Destination = B.Param5 + ' - ' + B.Param9
  --,	A.CallData = CASE B.Param1
		--			WHEN NULL THEN 'ANI: ' + A.CallData 
		--			WHEN '' THEN 'ANI: ' + A.CallData 
		--			ELSE 'ANI: ' + A.CallData + CHAR(13) + CHAR(10) + 'SIN: ' + B.Param1
		--		END
  FROM @tempCradleToGrave AS A
		INNER JOIN [VW12PCTIDB01\UIP_CONFIG].[detail_epro].[dbo].[MediaDataDetail] AS B ON B.SeqNum = A.SeqNum
WHERE A.UMID IS NULL


END 

 --SELECT UMID, CallDateTime, Event, Agent as HandledBy, Source, Destination, CallData, EventData  
 --FROM @tempCradleToGrave WHERE NOT(UMID IS NULL) AND UMID <> '' 


 SELECT * FROM @tempCradleToGrave where CallDateTime between @FromDateTime and @ToDateTime
  AND NOT(UMID IS NULL) AND UMID <> '' 
  --and Event <> 'M3'
 ORDER BY UMID, EventID, SeqNum, CallDateTime, rn

 
 
";
					
	//echo "<pre>$cmd</pre>";//die();
		$query = $this->db->query($cmd);
		return $query;
	}
	
	function get_vhis15_data($range,$dtFrom,$dtTo,$site,$agents,$service, $trunks) {
		$cmd = "DECLARE @FromDateTime datetime = '".$dtFrom."',@ToDateTime datetime = '".$dtTo."',@trunkid varchar(max) = '".$trunks."'
				SET NOCOUNT ON; 
			SELECT TrunkKey ,TrunkIdName ,Capacity ,AssignedApplication ,case when DownTime = 0 then 'Down' else 'Active' end as Status ,TargetTimeAvailable ,ActualTimeAvailable ,LastDateTimeTransaction FROM
			(SELECT DISTINCT _subTableDetails.[TrunkKey] ,_subTableDetails.TrunkIdName ,_subTableDetails.[DeviceCode] ,_subTableDetails.[DeviceName] ,_subTableDetails.[SubDeviceName] ,_subTableDetails.[Capacity] ,_subTableDetails.[TargetTimeAvailable] ,_subTableDetails.[AssignedApplication] ,_subTableDown.[DownTime] ,_subTableDown.ActualTimeAvailable as luma ,case when _subTableDown.[DownTime] = 0  then _subTableDetails.[TargetTimeAvailable] - isnull(_subTableDown.[ActualTimeAvailable],_subTableDetails.[TargetTimeAvailable])  when _subTableDown.[ActualTimeAvailable] <= 0 then _subTableDetails.[TargetTimeAvailable] else isnull(_subTableDown.[ActualTimeAvailable],_subTableDetails.[TargetTimeAvailable]) end as ActualTimeAvailable ,_subTableDetails.[LastDateTimeTransaction]
			FROM ( SELECT DISTINCT _trunkSubTable.[TrunkKey] ,(_trunkSubTable.[DeviceCode] + '@' + _trunkSubTable.[DeviceName]) AS TrunkIdName ,_trunkSubTable.[DeviceCode] ,_trunkSubTable.[DeviceName] ,_trunkSubTable.[SubDeviceName] ,_trunkSubTable.[Capacity] ,_trunkSubTable.[TargetTimeAvailable] ,_trunkSubTable.[AssignedApplication] ,tableLastDateTimeTransaction.[LastDateTimeTransaction]
			FROM (SELECT (_baseTrunkTable.[DeviceCode] + '@' + _baseTrunkTable.[DeviceName]) AS TrunkIdName ,_baseTrunkTable.[TrunkKey] ,_baseTrunkTable.[DeviceCode] ,_baseTrunkTable.[DeviceName] ,_baseTrunkTable.[SubDeviceName] ,'30' AS Capacity ,(DATEDIFF(\"s\", @FromDateTime, @ToDateTime)) AS TargetTimeAvailable ,_baseTrunkTable.[TrunkName] AS AssignedApplication
			FROM ( SELECT * FROM [CiscoRepDB].[dbo].[CiscoTrunkDetails] ) _baseTrunkTable WHERE (_baseTrunkTable.[DeviceCode] + '@' + _baseTrunkTable.[DeviceName]) IN(SELECT DeviceCode +'@'+ DeviceName FROM CiscoRepDB.dbo.CiscoTrunkDetails) ) _trunkSubTable
			LEFT JOIN ( (SELECT (_trunk.[DeviceCode] + '@' + _trunk.[DeviceName]) AS tempTrunkIdName ,(MAX((DATEADD(hour,8,(DATEADD(SECOND,CONVERT(BIGINT,_cdr.[dateTimeOrigination]),'19700101')))))) AS LastDateTimeTransaction FROM [REPDB].[dbo].[CiscoCDR] _cdr ,[CiscoRepDB].[dbo].[CiscoTrunkDetails] _trunk ,[CiscoRepDB].[dbo].[CiscoDowntimeInfo] _down2 WHERE _cdr.[origDeviceName] = (_trunk.[DeviceCode] + '@' + _trunk.[DeviceName]) AND  ( (DATEADD(hour,8,(DATEADD(SECOND,CONVERT(BIGINT,_cdr.[dateTimeOrigination]),'19700101'))) >= @FromDateTime) AND (DATEADD(hour,8,(DATEADD(SECOND,CONVERT(BIGINT,_cdr.[dateTimeOrigination]),'19700101'))) <= @ToDateTime) )
			GROUP BY _trunk.[DeviceCode], _trunk.[DeviceName] )   ) tableLastDateTimeTransaction ON tableLastDateTimeTransaction.tempTrunkIdName = _trunkSubTable.TrunkIdName ,[CiscoRepDB].[dbo].[CiscoDowntimeInfo] _down
			WHERE (_trunkSubTable.[DeviceCode] + '@' + _trunkSubTable.[DeviceName]) IN  ( SELECT DeviceCode +'@'+ DeviceName FROM CiscoRepDB.dbo.CiscoTrunkDetails )
			GROUP BY (_trunkSubTable.[DeviceCode] + '@' + _trunkSubTable.[DeviceName]) ,_trunkSubTable.[TrunkKey] ,_trunkSubTable.[DeviceCode] ,_trunkSubTable.[DeviceName] ,_trunkSubTable.[SubDeviceName] ,_trunkSubTable.[Capacity] ,_trunkSubTable.[TargetTimeAvailable] ,_trunkSubTable.[AssignedApplication] ,tableLastDateTimeTransaction.[LastDateTimeTransaction] ,_down.DeviceName ,_down.SubDeviceName ) _subTableDetails
			LEFT JOIN
			(SELECT _down.[DeviceName] ,_down.[SubDeviceName],(SUM(DATEDIFF(\"s\", ((CASE WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime AND _down.[DowntimeEnd] > @FromDateTime THEN @FromDateTime WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeStart] < @ToDateTime THEN @FromDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeStart] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN @FromDateTime END) )  ,((CASE WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeEnd]  WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime AND _down.[DowntimeStart] < @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeEnd] > @FromDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeStart] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeEnd] END) ) )) ) AS DownTime
			,((DATEDIFF(\"s\", @FromDateTime, @ToDateTime)) -  (SUM(DATEDIFF(\"s\", (CASE WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime AND _down.[DowntimeEnd] > @FromDateTime THEN @FromDateTime WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeStart] < @ToDateTime THEN @FromDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeStart] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeStart] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN @FromDateTime END )
			,(CASE WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime AND _down.[DowntimeStart] < @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeEnd] > @FromDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime AND _down.[DowntimeStart] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] < @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] = @FromDateTime AND _down.[DowntimeEnd] > @ToDateTime THEN @ToDateTime WHEN _down.[DowntimeStart] > @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeEnd] WHEN _down.[DowntimeStart] < @FromDateTime AND _down.[DowntimeEnd] = @ToDateTime THEN _down.[DowntimeEnd] END )))) ) AS ActualTimeAvailable FROM [CiscoRepDB].[dbo].[CiscoDowntimeInfo] _down GROUP BY _down.[DeviceName]	,_down.[SubDeviceName] ) _subTableDown ON _subTableDown.[DeviceName] = _subTableDetails.[DeviceName] AND _subTableDown.[SubDeviceName] = _subTableDetails.SubDeviceName ) _FinalQuery where TrunkIdName in ( '". str_replace(",","','",$trunks) ."' )";
		$query = $this->db->query($cmd);
		return $query;
	}
	function get_vhis19_ivrs_off($range,$dtFrom,$dtTo,$siteid,$skill,$service) {
		//echo $siteid;die();
		$siteid = rtrim($siteid,",");
		$sitearr = explode(",",$siteid);
		$sitefinal = "";
		foreach($sitearr as $key) {
			$sitefinal .= "'".$key."',";
		}
		
		$sitefinal = rtrim($sitefinal,",");
		$servicefinal = rtrim($service,",");
		$skillfinal = rtrim($skill,",");
		$cmd = "
		--vhis19 - Trend Reports %5BRUI%5D (1).pdf
			set nocount on;
			DECLARE @FromDateTime datetime;
			DECLARE @ToDateTime datetime; 
			SET @FromDateTime='".$dtFrom."'; 
			SET @ToDateTime='".$dtTo."'; 

			--Answered by IVR

	select distinct aa.SiteName as SiteName
	,datename(year, aa.Calldate) as 'xyear'
	,datename(month, aa.Calldate) as 'xmonth'
	,month(aa.Calldate) as month_int  
	,count(aa.SiteName)  xcount
	from 
(

 Select  UMID,Calldate,xDATE,XTIME,SiteName from(
	SELECT  RIGHT(UMID,22) as UMID, dateadd(hour, 0, CallDate) as Calldate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as xDATE, 
	CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as XTIME, 
	isnull(sites.SiteName,'MOC') as SiteName,(select top 1 (select top 1 Service_Id from REPDB..ACDCallDetail where SeqNum = mdc.SeqNum ) from Repdb.dbo.MediaDataDetail mdc where mdc.Param18 = ivr.SessionID COLLATE SQL_Latin1_General_CP1_CI_AS
	and mdc.Param19=ivr.UMID COLLATE SQL_Latin1_General_CP1_CI_AS) as Service_Id ,
	(select top 1 (select top 1 Skill_Id from REPDB..ASBRCallSkillDetail where SeqNum = mdc.SeqNum and Skill_Id is not null and Skill_Id 
	not in (4000001, 4000002)) from Repdb.dbo.MediaDataDetail mdc where mdc.Param18 = ivr.SessionID COLLATE SQL_Latin1_General_CP1_CI_AS
	and mdc.Param19=ivr.UMID COLLATE SQL_Latin1_General_CP1_CI_AS) as Skill_Id
	FROM  [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu] as ivr
	INNER JOIN [VW12PCTICXPDB01].[TransactionDB].[dbo].[TblHotlineNumbers] b on ivr.DNIS = b.AssociatedDNIS
	left join RepUIDB.dbo.DNIS xdnis
	on ivr.DNIS = xdnis.DNIS COLLATE SQL_Latin1_General_CP1_CI_AS
	left join RepUIDB.dbo.Sites sites
	on xdnis.SiteGuid = sites.SiteGuid

	WHERE 
	--[Medium] <> '' and
	--[Language] <> '' AND 
	--[Language] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND 
	dateadd(hour, 0, CallDate) BETWEEN @FromDateTime AND @ToDateTime ) as xx where xx.Service_Id in (".$servicefinal.") and xx.Skill_Id in (".$skillfinal.")

) as aa
where SiteName in(".$sitefinal.")
group by aa.SiteName,  month(aa.Calldate), datename(year, aa.Calldate),  datename(month, aa.Calldate)
order by aa.SiteName
			
		";
	//echo"<pre>$cmd</pre>";die();
	$query = $this->db->query($cmd);
// echo"<pre>";
// print_r($query);
// echo"</pre>";
// die();
		return $query;
	}	
	function get_vhis19_ivrs_ans($range,$dtFrom,$dtTo,$siteid,$skill,$service) {
		//echo $siteid;die();
		$siteid = rtrim($siteid,",");
		$sitearr = explode(",",$siteid);
		$sitefinal = "";
		foreach($sitearr as $key) {
			$sitefinal .= "'".$key."',";
		}
		
		$sitefinal = rtrim($sitefinal,",");
		$servicefinal = rtrim($service,",");
		$skillfinal = rtrim($skill,",");
		$cmd = "--vhis19 - Trend Reports %5BRUI%5D (1).pdf
			set nocount on;
			DECLARE @FromDateTime datetime;
			DECLARE @ToDateTime datetime; 
			SET @FromDateTime='".$dtFrom."'; 
			SET @ToDateTime='".$dtTo."'; 

			--Answered by IVR

	select distinct aa.SiteName as SiteName
	,datename(year, aa.Calldate) as 'xyear'
	,datename(month, aa.Calldate) as 'xmonth'
	,month(aa.Calldate) as month_int  
	,count(aa.SiteName)  xcount
	from 
(

 Select  UMID,Calldate,xDATE,XTIME,SiteName from(
	SELECT  RIGHT(UMID,22) as UMID, dateadd(hour, 0, CallDate) as Calldate, CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),101) as xDATE, 
	CONVERT(NVARCHAR,CAST(CallDate AS DATETIME),108) as XTIME, 
	isnull(sites.SiteName,'MOC') as SiteName,(select top 1 (select top 1 Service_Id from REPDB..ACDCallDetail where SeqNum = mdc.SeqNum ) from Repdb.dbo.MediaDataDetail mdc where mdc.Param18 = ivr.SessionID COLLATE SQL_Latin1_General_CP1_CI_AS
	and mdc.Param19=ivr.UMID COLLATE SQL_Latin1_General_CP1_CI_AS) as Service_Id ,
	(select top 1 (select top 1 Skill_Id from REPDB..ASBRCallSkillDetail where SeqNum = mdc.SeqNum and Skill_Id is not null and Skill_Id 
	not in (4000001, 4000002)) from Repdb.dbo.MediaDataDetail mdc where mdc.Param18 = ivr.SessionID COLLATE SQL_Latin1_General_CP1_CI_AS
	and mdc.Param19=ivr.UMID COLLATE SQL_Latin1_General_CP1_CI_AS) as Skill_Id
	FROM  [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu] as ivr
	INNER JOIN [VW12PCTICXPDB01].[TransactionDB].[dbo].[TblHotlineNumbers] b on ivr.DNIS = b.AssociatedDNIS
	left join RepUIDB.dbo.DNIS xdnis
	on ivr.DNIS = xdnis.DNIS COLLATE SQL_Latin1_General_CP1_CI_AS
	left join RepUIDB.dbo.Sites sites
	on xdnis.SiteGuid = sites.SiteGuid

	WHERE 
	--[Medium] <> '' and
	--[Language] <> '' AND 
	--[Language] NOT IN ('Abandoned', 'Terminated', 'Transfer') AND 
	dateadd(hour, 0, CallDate) BETWEEN @FromDateTime AND @ToDateTime ) as xx where xx.Service_Id in (".$servicefinal.") and xx.Skill_Id in (".$skillfinal.")

) as aa
where SiteName in(".$sitefinal.")
group by aa.SiteName,  month(aa.Calldate), datename(year, aa.Calldate),  datename(month, aa.Calldate)
order by aa.SiteName
			
			";
	//echo"<pre>$cmd</pre>";
	$query = $this->db->query($cmd);
// echo"<pre>";
// print_r($query);
// echo"</pre>";
// die();
		return $query;
	}	
	function get_vhis19_ave_num($range,$dtFrom,$dtTo,$siteid,$skill,$service) {
		//echo $siteid;die();
		$siteid = rtrim($siteid,",");
		$sitearr = explode(",",$siteid);
		$sitefinal = "";
		foreach($sitearr as $key) {
			$sitefinal .= "'".$key."',";
		}
		
		$sitefinal = rtrim($sitefinal,",");
		$servicefinal = rtrim($service,",");
		$skillfinal = rtrim($skill,",");
		
		$cmd = "
		--vhis19 - Trend Reports %5BRUI%5D (1).pdf
			set nocount on;
			DECLARE @FromDateTime datetime;
			DECLARE @ToDateTime datetime; 
			SET @FromDateTime='".$dtFrom."'; 
			SET @ToDateTime='".$dtTo."';

			select distinct SiteName,
			--, Service_Id
			--, skill_id 
			 xyear, xmonth, month_int, avg(userCount) as avg_agent_count  from (

			select distinct aa.SiteName as SiteName
				,Service_Id
				,skill_id
				,datename(year, aa.CallStartDt) as 'xyear'
				,datename(month, aa.CallStartDt) as 'xmonth'
				,month(aa.CallStartDt) as month_int
				,User_Id
				,count(distinct User_Id) as userCount
				
				
				from 
					(

				select 
						acd.SeqNum, acd.User_Id
						,st.SiteName
						,acd.Service_Id
						,(select top 1 Skill_Id from REPDB..ASBRCallSkillDetail where Service_Id = acd.Service_Id and SeqNum = acd.SeqNum and Skill_Id not in (4000001, 4000002)) as skill_id
						
						,format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') as CallDate
						,dateadd(hour, 8, acd.QueueStartDt) as QueueStartDt
						,dateadd(hour, 8, acd.QueueEndDt) as QueueEndDt
						,dateadd(hour, 8, acd.CallStartDt) as CallStartDt	
						,dateadd(hour, 8, acd.CallEndDt) as CallEndDt
						,dateadd(hour, 8, acd.WrapEndDt) as WrapEndDt
						,acd.CallActionId
						,iif(acd.CallActionId=3,1,0) as OFFERED_TO_AGENT
						,1 OFFERED			
						,iif(isnull(acd.User_Id,0)='0',0,1) ANSWERED
						,iif(isnull(acd.User_Id,0)='0',1,0) ABANDONED
						,convert(int, iif(iif(isnull(acd.User_Id,0)='0',0,1)=0,0,
							iif(isnull(datediff(second, acd.queueStartDt, acd.queueEndDt),0)<=20,1,0) 
							
						)) as ANSwithinThreshold
						,isnull(datediff(second, acd.QueueEndDt, acd.WrapEndDt),0) as HandlingTime
						,iif(iif(isnull(acd.User_Id,0)='0',0,1)=0,0,
							isnull(datediff(second, acd.queueStartDt, acd.queueEndDt),0) 
						) as AnswerDelay
						,users.DistinctUserID
						--,acd.Station
						
				from 
						repdb.dbo.ACDCallDetail acd
						left join repdb.dbo.ACDCallDetail d
							on acd.SeqNum = d.SeqNum and acd.CallId = d.CallId
						left join RepUIDB.dbo.Stations s
							on d.Station = s.Station
						left join RepUIDB.dbo.Sites st
							on s.SiteGuid = st.SiteGuid
						left join (	select distinct format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') as UserCallDate, 
									count(distinct acd.User_Id) as DistinctUserID
								from repdb.dbo.ACDCallDetail acd where acd.User_Id is not null
								group by format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') ) as users
								on UserCallDate=format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd')

				where Convert(varchar, acd.CallStartDt, 101) >= @FromDateTime AND Convert(varchar, acd.CallStartDt, 101) <= @ToDateTime
				and SiteName is not null
				--where format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd')='2019-03-01'
				
				
				)as aa

			group by
			aa.SiteName, Service_iD, Skill_Id, datename(year, aa.CallStartDt), datename(month, aa.CallStartDt), month(aa.CallStartDt), User_Id
			--order by SiteName, month_int
			) aaa
			where  SiteName in (".$sitefinal.")
			and Service_Id in (".$servicefinal.")
			and skill_id in(".$skillfinal.")
			group by SiteName
			--,Service_Id
			--,skill_id 
			,xyear, xmonth, month_int
			order by SiteName
			

	
			";
					//	echo"<pre>$cmd</pre>";die();
					$query = $this->db->query($cmd);

		return $query;
	}
	function get_vhis19_data($range,$dtFrom,$dtTo,$siteid,$skill,$service) {
		//echo $siteid;die();
		$siteid = rtrim($siteid,",");
		$sitearr = explode(",",$siteid);
		$sitefinal = "";
		foreach($sitearr as $key) {
			$sitefinal .= "'".$key."',";
		}
		
		$sitefinal = rtrim($sitefinal,",");
		$servicefinal = rtrim($service,",");
		$skillfinal = rtrim($skill,",");
		
		$cmd = "--vhis19 - Trend Reports %5BRUI%5D (1).pdf
					DECLARE @FromDateTime datetime;
					DECLARE @ToDateTime datetime; 
					SET @FromDateTime='2019-01-01 00:00:00'; 
					SET @ToDateTime='2019-12-31 23:59:59';

					select distinct aa.SiteName as SiteName
						,datename(year, aa.CallStartDt) as 'year'
						,datename(month, aa.CallStartDt) as 'month'
						,month(aa.CallStartDt) as month_int
						,avg(aa.HandlingTime) as aht_sec 
						,CONVERT(time(0), DATEADD(SECOND, avg(aa.HandlingTime), 0)) as aht_time
						,avg(aa.AnswerDelay) as asa_sec
						,CONVERT(time(0), DATEADD(SECOND, avg(aa.AnswerDelay), 0)) as asa_time
						--transfered
						

						,count(OFFERED) sum_call_registered
						

						--,sum(ANSWERED) sum_answered
						,(case when acd.CallActionId = 3 or acd.CallActionId = 8 then 1 end) as ANSWERED
						,sum(ANSwithinThreshold) sum_ans_w20
						,sum(aa.OFFERED_TO_AGENT) as calls_offered_to_agent
						
						,sum(abandoned) as sum_abandoned
							
						,avg(aa.DistinctUserID) as avg_no_of_agents
						
						,iif(count(offered)=0,0,
							convert(decimal(10,2),(convert(float,sum(ANSWERED))/convert(float,count(OFFERED))) * 100)
						)as answer_level
						,iif(count(offered)=0,0,
							convert(decimal(10,2),(convert(float,sum(ANSwithinThreshold))/convert(float,count(OFFERED))) * 100)
						)as service_level
						
						
						from 
							(

						select 
								acd.SeqNum, acd.User_Id
								,st.SiteName
								
								,format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') as CallDate
								,dateadd(hour, 8, acd.QueueStartDt) as QueueStartDt
								,dateadd(hour, 8, acd.QueueEndDt) as QueueEndDt
								,dateadd(hour, 8, acd.CallStartDt) as CallStartDt	
								,dateadd(hour, 8, acd.CallEndDt) as CallEndDt
								,dateadd(hour, 8, acd.WrapEndDt) as WrapEndDt
								,acd.CallActionId
								,iif(acd.CallActionId=3,1,0) as OFFERED_TO_AGENT
								,1 OFFERED			
								,iif(isnull(acd.User_Id,0)='0',0,1) ANSWERED
								,iif(isnull(acd.User_Id,0)='0',1,0) ABANDONED
								,convert(int, iif(iif(isnull(acd.User_Id,0)='0',0,1)=0,0,
									iif(isnull(datediff(second, acd.queueStartDt, acd.queueEndDt),0)<=20,1,0) 
									
								)) as ANSwithinThreshold
								,isnull(datediff(second, acd.QueueEndDt, acd.WrapEndDt),0) as HandlingTime
								,iif(iif(isnull(acd.User_Id,0)='0',0,1)=0,0,
									isnull(datediff(second, acd.queueStartDt, acd.queueEndDt),0) 
								) as AnswerDelay
								,users.DistinctUserID
								--,acd.Station
								
						from 
								repdb.dbo.ACDCallDetail acd
								left join repdb.dbo.ACDCallDetail d
									on acd.SeqNum = d.SeqNum and acd.CallId = d.CallId
								left join RepUIDB.dbo.Stations s
									on d.Station = s.Station
								left join RepUIDB.dbo.Sites st
									on s.SiteGuid = st.SiteGuid
								left join (	select distinct format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') as UserCallDate, 
											count(distinct acd.User_Id) as DistinctUserID
										from repdb.dbo.ACDCallDetail acd where acd.User_Id is not null
										group by format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') ) as users
										on UserCallDate=format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd'),
										REPDB..ASBRCallSkillDetail x

						where Convert(varchar, acd.CallStartDt, 101) >= @FromDateTime AND Convert(varchar, acd.CallStartDt, 101) <= @ToDateTime
						--where format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd')='2019-03-01'
						and  acd.QueueEndDt is not null 
						and x.Service_Id = acd.Service_Id and x.SeqNum = acd.SeqNum and x.Skill_Id not in (4000001, 4000002)
						
						)as aa
					WHERE aa.SiteName IN(".$sitefinal.")
					group by
					aa.SiteName,  datename(year, aa.CallStartDt), datename(month, aa.CallStartDt), month(aa.CallStartDt) 
					order by SiteName, month_int";
		$cmd = "--vhis19 - Trend Reports %5BRUI%5D (1).pdf
					DECLARE @FromDateTime datetime;
					DECLARE @ToDateTime datetime; 
					SET @FromDateTime='".$dtFrom."'; 
					SET @ToDateTime='".$dtTo."';

					select distinct aa.SiteName as SiteName
	,datename(year, aa.CallStartDt) as 'year'
	,datename(month, aa.CallStartDt) as 'month'
	,month(aa.CallStartDt) as month_int
	,avg(aa.HandlingTime) as aht_sec 
	--,CONVERT(time(0), DATEADD(SECOND, (sum(aa.HandlingTime) / count(ANSWERED) ),0)) as aht_time --CONVERT(time(0), DATEADD(SECOND, sum(aa.HandlingTime), 0)) as aht_time
	--,CONVERT(time(0), DATEADD(SECOND,round(convert(decimal(10,2),(convert(float,sum(aa.HandlingTime))) / (convert(float,count(ANSWERED)))),2),0)) as aht_time
	,CONVERT(time(0), DATEADD(SECOND,round(round(convert(decimal(10,2),(convert(float,sum(aa.HandlingTime))) / count(ANSWERED)),2),0),0)) as aht_time
	,avg(aa.AnswerDelay) as asa_sec
	--,CONVERT(time(0), DATEADD(SECOND, avg(aa.AnswerDelay), 0)) as asa_time
	,CONVERT(time(0), DATEADD(SECOND,round(round(convert(decimal(10,2),(convert(float,sum(aa.AnswerDelay))) / count(ANSWERED)),2),0),0)) as asa_time
	--transfered
	

	,count(OFFERED) sum_call_registered
	

	--,sum(ANSWERED) sum_answered
	,sum(ANSwithinThreshold) sum_ans_w20
	,sum(aa.OFFERED_TO_AGENT) as calls_offered_to_agent
	
	,sum(abandoned) as sum_abandoned
		
	,avg(aa.DistinctUserID) as avg_no_of_agents
	
	,iif(count(offered)=0,0,
		convert(decimal(10,2),(convert(float,sum(ANSWERED))/convert(float,count(OFFERED))) * 100)
	)as answer_level
	,iif(count(offered)=0,0,
		convert(decimal(10,2),(convert(float,sum(ANSwithinThreshold))/convert(float,count(OFFERED))) * 100)
	)as service_level
	
	
	from 
		(

	select 
			acd.SeqNum, acd.User_Id
			,isnull(st.SiteName,'MOC') SiteName
			
			,format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') as CallDate
			,dateadd(hour, 8, acd.QueueStartDt) as QueueStartDt
			,dateadd(hour, 8, acd.QueueEndDt) as QueueEndDt
			,dateadd(hour, 8, acd.CallStartDt) as CallStartDt	
			,dateadd(hour, 8, acd.CallEndDt) as CallEndDt
			,dateadd(hour, 8, acd.WrapEndDt) as WrapEndDt
			,acd.CallActionId
			,iif(acd.CallActionId=3,1,0) as OFFERED_TO_AGENT
			,1 OFFERED			
			--,iif(isnull(acd.User_Id,0)='0',0,1) ANSWERED
			,(case when acd.CallActionId = 3 or acd.CallActionId = 8 then 1 end) as ANSWERED
			
			,iif(isnull(acd.User_Id,0)='0',1,0) ABANDONED
			,convert(int, iif(iif(isnull(acd.User_Id,0)='0',0,1)=0,0,
				iif(isnull(datediff(second, acd.queueStartDt, acd.queueEndDt),0)<=20,1,0) 
				
			)) as ANSwithinThreshold
			,isnull(datediff(second, acd.QueueEndDt, acd.WrapEndDt),0) as HandlingTime
			,iif(iif(isnull(acd.User_Id,0)='0',0,1)=0,0,
				isnull(datediff(second, acd.queueStartDt, acd.queueEndDt),0) 
			) as AnswerDelay
			,users.DistinctUserID
			--,acd.Station
			
	from 
			repdb.dbo.ACDCallDetail acd
			left join repdb.dbo.ACDCallDetail d
				on acd.SeqNum = d.SeqNum and acd.CallId = d.CallId
			left join RepUIDB.dbo.Stations s
				on d.Station = s.Station
			left join RepUIDB.dbo.Sites st
				on s.SiteGuid = st.SiteGuid
			left join (	select distinct format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') as UserCallDate, 
						count(distinct acd.User_Id) as DistinctUserID
					from repdb.dbo.ACDCallDetail acd where acd.User_Id is not null
					group by format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd') ) as users
					on UserCallDate=format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd'),
					REPDB..ASBRCallSkillDetail x

	where Convert(varchar, acd.CallStartDt, 101) >= @FromDateTime AND Convert(varchar, acd.CallStartDt, 101) <= @ToDateTime
	--where format(dateadd(hour, 8, acd.CallStartDt),'yyyy-MM-dd')='2019-03-01'
	and  acd.QueueEndDt is not null
	and x.Service_Id = acd.Service_Id and x.SeqNum = acd.SeqNum and x.Skill_Id not in (4000001, 4000002)
	)as aa
					WHERE aa.SiteName IN(".$sitefinal.")
					group by
					aa.SiteName,  datename(year, aa.CallStartDt), datename(month, aa.CallStartDt), month(aa.CallStartDt) 
					order by SiteName, month_int";
	//UPDATED				
		$cmd = "
DECLARE @FromDateTime datetime;
DECLARE @ToDateTime datetime; 
SET @FromDateTime='".$dtFrom."'; 
SET @ToDateTime='".$dtTo."';

declare @tmpApplicationPerformanceV2 table (
	[15MinsInterval] datetime,
	[30MinsInterval] datetime,
	[60MinsInterval] datetime,
	Sunday varchar(50),
	Saturday varchar(50),
	Minus15Mins varchar(50),
	Minus30Mins varchar(50),
	Minus60Mins varchar(50),
	Week_count int,
	Day_Count int,
	Day_Name varchar(50),
	perDate varchar(50),
	Month_Count int,
	MONTH_NAME varchar(50),
	Year_Month varchar(50),
	YEAR int,
	DATE_TIME datetime,
	Service_Shift varchar(50),
	Offered int,
	Answered int,
	Abandoned int,
	CallsOverflowed int,
	CallDelay int,
	MaxCallDelay int,
	DelaySkillset int,
	AfterThreshold int,
	WithinThreshold int,
	HANDLING_TIME INT,
	MaxHandlingTime int,
	MinHandlingTime int,
	AbandonedDelay int,
	AbandonedAfterThreshold int,
	SERVICE_C varchar(50),
	Service_Id int,
	SiteName varchar(20),
	RepeatCalls int,
	ANICount int
);

-- Create Fifteen Minute Intervals
with DateRange([15MinsInterval]) as 
(
    select Dateadd(minute, 0, @FromDateTime)
    union all
    select Dateadd(minute, 15, [15MinsInterval])
    from DateRange  
    where Dateadd(minute, 0, [15MinsInterval]) < Dateadd(minute, -15, @ToDateTime)
),
-- Base Call Details
incoming_calls as (
select 
distinct(acdCallDetail.SeqNum) as seqnum,
dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, acdCallDetail.CallStartDt)) / 15 * 15, 0)) as FifteenMinutesInterval,
acdCallDetail.CallStartDt as callstartdt,
acdCallDetail.CallId as callid,
acdCallDetail.CallTypeId as calltypeid,
acdCallDetail.CallActionId as callactionid,
acdCallDetail.CallActionReasonId as callactionreasonid,
acdCallDetail.User_Id as [user_id],
acdCallDetail.Station as station,
acdCallDetail.ANI as ani,
acdCallDetail.Service_Id as service_id,
[service].Service_c as service_c,
(select top 1 Skill_Id from REPDB..ASBRCallSkillDetail where Service_Id = acdCallDetail.Service_Id and SeqNum = acdCallDetail.SeqNum and Skill_Id not in (4000001, 4000002)) as skill_id,
--asbrCallSkillDetail.Skill_Id as skill_id,
--skills.Skill_Desc as skill_desc,
media.Param1 as [sin],
media.Param2 as contactnumber,
media.Param5 as param5,
media.Param9 as param9,
media.Param15 as dnis,
acdCallDetail.QueueStartDt as queuestartdt,
acdCallDetail.QueueEndDt as queueenddt,
acdCallDetail.WrapEndDt as wrapenddt,
isnull(sites.SiteName, 'MOC') as sitename
from REPDB..ACDCallDetail acdCallDetail
left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] [service] 
	on acdCallDetail.Service_Id = [service].Service_Id
left join REPDB..MediaDataDetail media 
	on acdCallDetail.SeqNum = media.SeqNum and acdCallDetail.callid = media.CallId
--left join RepUIDB..Stations stations 
--	on acdCallDetail.Station = stations.Station
--left join RepUIDB..Sites sites 
--	on sites.SiteGuid = stations.SiteGuid
--left join RepUIDB..DNIS dnis on media.param15 = dnis.DNIS
--------------------------------------------------------------------------------
left join RepUIDB..Stations stations on acdCallDetail.Station = stations.Station
left join RepUIDB..Sites sites on stations.SiteGuid = sites.SiteGuid -- dnis.SiteGuid = sites.SiteGuid
--------------------------------------------------------------------------------
--left join REPDB..AgentLoginLogout l on acdCallDetail.User_Id = l.User_Id and l.Service_Id = 0 and acdCallDetail.CallStartDt between l.LoginDt and l.LogoutDt
--left join REPDB..AgentStateAudit s on l.User_Id = s.User_Id and dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, l.LoginDt)) / 1 * 1, 0)) = dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, s.ModifiedDt)) / 1 * 1, 0)) --DATEADD(ms, -datepart(ms, l.LoginDt), l.LoginDt) = dateadd(ms, -datepart(ms, s.Status_Start_dt), s.Status_Start_dt)
--left join RepUIDB..Stations station on s.Station = station.Station and acdCallDetail.Station = s.Station
--left join RepUIDB..Sites sites on station.SiteGuid = sites.SiteGuid
--------------------------------------------------------------------------------
where dateadd(hour, 8, acdCallDetail.CallStartDt) >= dateadd(d, 0, @FromDateTime) and  dateadd(hour, 8, acdCallDetail.CallStartDt) <= dateadd(d, 0, @ToDateTime)
--and media.Service_Id in (4000013, 4000019, 4000021, 4000023, 4000025, 40000027, 40000029)
--and acdCallDetail.Service_Id in ((select Service_Id from @selectedServiceIds))
--and acdCallDetail.Service_Id in (4000013,4000018)
--and CallActionId NOT in (14)
),
incoming_calls_skills as (
	select
	incoming.*,
	(select top 1 Skill_Desc from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Skills where Skill_Id = incoming.skill_id) as skill_desc
	from
	incoming_calls incoming
	--where 
	--incoming.Skill_Id in (@Skill_Ids) or (incoming.skill_id is null and incoming.callactionid = 5)
	--incoming.Skill_Id in (4000042,4000036,4000039,4000023,4000041,4000034,4000031,4000035,4000037,4000040,4000033,4000043,4000032,4000038,4000050,4000051,4000048,4000049,4000044,4000045,4000047,4000046,4000008,4000026,4000024,4000019,4000009,4000029,4000011,4000012,4000020,4000010,4000017,4000003,4000015,4000028,4000014,4000022,4000005,4000007,4000013,4000016,4000021,4000027,4000006,4000030,4000052) or (incoming.skill_id is null and incoming.callactionid = 5)
),
-- Get all handled from incoming calls
handled_base as (
	select
	incoming.FifteenMinutesInterval,
	incoming.callstartdt,
	incoming.seqnum,
	incoming.callid,
	incoming.callactionid,
	incoming.[sin],
	incoming.[ani],
	incoming.service_id,
	incoming.service_c,
	incoming.skill_id,
	incoming.skill_desc,
	incoming.param5,
	incoming.param9,
	incoming.dnis,
	incoming.SiteName,
	incoming.[user_id],
	incoming.station,
	datediff(s, incoming.queuestartdt, incoming.queueenddt) as answerdelay,
	datediff(s, incoming.queuestartdt, incoming.queueenddt) as skillsetanswerdelay,
	datediff(s, incoming.queueenddt, incoming.wrapenddt) as handlingtime
	from
	incoming_calls_skills incoming
	where incoming.callactionid in (3, 8) and queuestartdt is not null
),
-- Get all abandoned from incoming calls
abandoned_base as (
	select
	incoming.FifteenMinutesInterval,
	incoming.callstartdt,
	incoming.seqnum,
	incoming.callid,
	incoming.callactionid,
	incoming.[sin],
	incoming.[ani],
	incoming.service_id,
	incoming.service_c,
	incoming.skill_id,
	incoming.skill_desc,
	incoming.param5,
	incoming.param9,
	incoming.dnis,
	incoming.SiteName,
	incoming.[user_id],
	incoming.station,
	datediff(s, incoming.queuestartdt, incoming.queueenddt) as abandondelay,
	0 as skillsetabandondelay,
	0 as handlingtime
	FROM 
	incoming_calls_skills incoming
	left join RepUIDB..DNIS d on incoming.dnis = d.DNIS
	left join RepUIDB..Sites s on d.SiteGuid = s.SiteGuid
	WHERE 
	incoming.callactionid IN (5,6)
),
-- Get all overflowed from incoming calls
overflowed_base as (
	select
	incoming.FifteenMinutesInterval,
	incoming.callstartdt,
	incoming.seqnum,
	incoming.callid,
	incoming.callactionid,
	incoming.[sin],
	incoming.[ani],
	incoming.service_id,
	incoming.service_c,
	incoming.skill_id,
	incoming.skill_desc,
	incoming.param5,
	incoming.param9,
	incoming.dnis,
	incoming.SiteName,
	incoming.[user_id],
	incoming.station,
	datediff(s, incoming.queuestartdt, incoming.queueenddt) as overflowdelay,
	datediff(s, incoming.queuestartdt, incoming.queueenddt) as skillsetoverflowdelay,
	0 as handlingtime
	from
	incoming_calls_skills incoming
	where
	incoming.callactionid = 18
),
-- Get Agent Login Site Info
agent_site_login as (
	select
	l.LoginDt,
	l.LogoutDt,
	l.User_Id, 
	s.Station,
	sites.SiteName as agent_site,
	[service].Service_Id as service_id,
	[service].Service_c as service_c
	from
	REPDB..AgentLoginLogout l
	left join REPDB..AgentStateAudit s on l.User_Id = s.User_Id and DATEADD(ms, -datepart(ms, l.LoginDt), l.LoginDt) = dateadd(ms, -datepart(ms, s.Status_Start_dt), s.Status_Start_dt)
	-----------------------------------------------------------------------
	--dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, l.LoginDt)) / 1 * 1, 0)) = dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, s.ModifiedDt)) / 1 * 1, 0))
	-----------------------------------------------------------------------
	left join RepUIDB..Stations station on s.Station = station.Station
	left join RepUIDB..Sites sites on station.SiteGuid = sites.SiteGuid
	left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] [service] on l.Service_Id = [service].Service_Id
	where --s.Station is not null
	s.Agent_Index is not null
	------------------------------------------------------------------------
	and (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or l.LogoutDt is null))
	------------------------------------------------------------------------
	--and (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or (dateadd(hour, 8, l.LoginDt) >= @FromDateTime and l.LogoutDt is null))
	------------------------------------------------------------------------
	--and l.Service_Id in ((select Service_Id from @selectedServiceIds))
	--and l.Service_Id in (4000013,4000018)
	--(4000013,4000018,4000019,4000020,4000021,4000022,4000023,4000024,4000025,4000026,4000027,4000028,4000029,4000030) 
	--(4000013, 4000019, 4000021, 4000023, 4000025, 40000027, 40000029)
),
-- Tag proper abandoned site
abandoned_agent_site as (
	select
	ab.FifteenMinutesInterval,
	ab.callstartdt,
	ab.service_id,
	ab.service_c,
	null as skill_id,
	null as skill_desc,
	ab.seqnum,
	ab.callid,
	ab.callactionid,
	ab.ani,
	ab.dnis,
	ab.sitename,
	'' as station,
	isnull(agt.agent_site, 'MOC') as proper_site_tagging,
	count(distinct agt.User_Id) as number_of_online_agents,
	0 as answered_flag,
	0 as overflow_flag,
	ab.param5 as last_ivr,
	ab.[sin],
	0 as repeat_count,
	ab.abandondelay as [delay],
	ab.skillsetabandondelay as [skillsetdelay],
	ab.handlingtime as [handlingtime],
	case when ab.abandondelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold
	from
	abandoned_base ab
	left join agent_site_login agt on (ab.callstartdt between agt.LoginDt and agt.LogoutDt or ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) and ab.SiteName = agt.agent_site -- or (ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
	left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on ab.service_id = asbr.Service_Id
	group by ab.FifteenMinutesInterval, ab.service_id, ab.service_c, ab.callstartdt, ab.SeqNum, ab.callid, ab.ani, ab.dnis, ab.SiteName, agt.agent_site, ab.callactionid, ab.param5, ab.[sin], ab.abandondelay, asbr.TargetQTime, ab.skillsetabandondelay, ab.handlingtime
),
-- Tag proper handled site
handled_agent_site as (
	select
	hd.FifteenMinutesInterval,
	hd.callstartdt,
	hd.service_id,
	hd.service_c,
	hd.skill_id,
	hd.skill_desc,
	hd.seqnum,
	hd.callid,
	hd.callactionid,
	hd.ani,
	hd.dnis,
	hd.sitename,
	hd.station,
	isnull(agt.agent_site, 'MOC') as proper_site_tagging,
	count(distinct agt.User_Id) as number_of_online_agents,
	1 as answered_flag,
	0 as overflow_flag,
	hd.param5 as last_ivr,
	hd.[sin],
	1 as repeat_count,
	hd.answerdelay as [delay],
	hd.skillsetanswerdelay as [skillsetdelay],
	hd.handlingtime as [handlingtime],
	case when hd.answerdelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold
	from
	handled_base hd
	left join agent_site_login agt 
		on 
			hd.[user_id] = agt.[User_Id] and
			--hd.service_id = agt.service_id and 
			--hd.station = agt.Station and
			(hd.callstartdt between agt.LoginDt and agt.LogoutDt or hd.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
	left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on hd.service_id = asbr.Service_Id
	group by hd.FifteenMinutesInterval, hd.service_id, hd.service_c, hd.skill_id, hd.skill_desc, hd.callstartdt, hd.seqnum, hd.callid, hd.ani, hd.dnis, hd.station, hd.sitename, agt.agent_site, hd.callactionid, hd.param5, hd.[sin], hd.answerdelay, asbr.TargetQTime, hd.skillsetanswerdelay, hd.handlingtime --, hd.dnis, hd.SiteName, hd.queuestartdt, hd.queueenddt, hd.wrapenddt, hd.service_id
),
-- Tag proper overflowed site
overflowed_agent_site as (
	select
	ov.FifteenMinutesInterval,
	ov.callstartdt,
	ov.service_id,
	ov.service_c,
	null as skill_id,
	null as skill_desc,
	ov.seqnum,
	ov.callid,
	ov.callactionid,
	ov.ani,
	ov.dnis,
	ov.sitename,
	'' as station,
	isnull(agt.agent_site, 'MOC') as proper_site_tagging,
	count(distinct agt.User_Id) as number_of_online_agents,
	null as answered_flag,
	1 as overflow_flag,
	ov.param5 as last_ivr,
	ov.[sin],
	0 as repeat_count,
	ov.overflowdelay as [delay],
	ov.skillsetoverflowdelay as [skillsetdelay],
	ov.handlingtime as [handlingtime],
	case when ov.overflowdelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold
	from
	overflowed_base ov
	left join agent_site_login agt on (ov.callstartdt between agt.LoginDt and agt.LogoutDt or ov.callstartdt >= agt.LoginDt and agt.LogoutDt is null) and ov.SiteName = agt.agent_site -- or (ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
	left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on ov.service_id = asbr.Service_Id
	group by ov.FifteenMinutesInterval, ov.service_id, ov.service_c, ov.callstartdt, ov.SeqNum, ov.callid, ov.ani, ov.dnis, ov.SiteName, agt.agent_site, ov.callactionid, ov.param5, ov.[sin], ov.overflowdelay, asbr.TargetQTime, ov.skillsetoverflowdelay, ov.handlingtime
),
-- Merge Handled + Abandoned
offered_calls as (
	select * from handled_agent_site
	union all
	select * from abandoned_agent_site
	union all
	select * from overflowed_agent_site
),
-- Set Flat Table Values
flat_table as (
	select distinct
	incoming.FifteenMinutesInterval as \"15MinsInterval\"
	,DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30,incoming.FifteenMinutesInterval)) / 30 * 30, 0)) as \"30MinsInterval\"
	,DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60,incoming.FifteenMinutesInterval)) / 60 * 60, 0)) as \"60MinsInterval\"
	,CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval))) %7 , dateadd(hour, 0,incoming.FifteenMinutesInterval))), 101) as Sunday
	,CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval))) %7 , dateadd(hour, 0,incoming.FifteenMinutesInterval))), 101) as Saturday
	,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
		format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 15 * 15, 0)) as Datetime), 'hh:mm:ss.fff') as Minus15Mins
	,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
		format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 30 * 30, 0)) as Datetime), 'hh:mm:ss.fff') as Minus30Mins
	,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
		format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 60 * 60, 0)) as Datetime), 'hh:mm:ss.fff') as Minus60Mins
	,datepart(wk, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Week_count
	,datepart(w, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Day_Count
	,datename(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Day_Name
	,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) as perDate
	,DATEPART(M, DATEADD(hour, 0,incoming.FifteenMinutesInterval)) AS Month_Count
	
	,cast(datename(m, dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) AS MONTH_NAME
	,cast(year(dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) as Year_Month
	,YEAR(DATEADD(hour, 0,incoming.FifteenMinutesInterval)) AS YEAR
	,DATEADD(hour, 0,incoming.FifteenMinutesInterval) AS DATE_TIME
	,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) +' '+
		case when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 5 or datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) >= 22 then '10PM - 6AM'
		when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) > = 14 and datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 21 then '2PM - 10PM'
		when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) >= 6 and datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 13 then '6AM - 2PM' end
		as Service_Shift
	,isnull(incoming.answered_flag, 0) as Answered
	,case when incoming.answered_flag = 0 then 1 else 0 end as Abandoned
	,isnull(incoming.overflow_flag, 0) as CallsOverFlowed
	--,isnull(incoming.delay,0) as AnsweredDelay
	,case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then isnull(incoming.delay, 0) else 0 end as AnsweredDelay
	,case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then isnull(incoming.skillsetdelay, 0) else 0 end as AnsDelayAtSkillset
	,(case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 0 then 1 else 0 end) else 0 end) as AnsAfterThreshold
	,(case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 1 then 1 else 0 end) else 0 end) as AnsWithinThreshold
	,isnull(incoming.handlingtime, 0) as HandlingTime
	,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then incoming.[delay] else 0 end) as AbandonDelay
	,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (incoming.[delay] > 20) then 1  else 0 end )else 0 end) as AbanAfterThreshold
	--,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 1 then 1 else 0 end) else 0 end) as AbanAfterThreshold
	,incoming.service_c
	,incoming.service_id
	,incoming.skill_id
	,incoming.sitename as SiteName
	,incoming.seqnum
	,incoming.callactionid
	,incoming.callstartdt
	,incoming.ani
	,incoming.repeat_count
	from 
	offered_calls incoming
),
-- Set instance of each ani within 24 hours
partitioned_table as (
	SELECT *, 
		ROW_NUMBER() OVER(
		PARTITION BY flat_table.ani, flat_table.service_id, flat_table.skill_id, (DATEADD(minute, (DATEDIFF(minute, '', flat_table.callstartdt)/1440)*1440, '')) --, flat_table.skill_id
		ORDER BY flat_table.ani, flat_table.callstartdt) AS ani_instance
	FROM flat_table
),
-- Set Repeat Count -1 for ani_instance = 1
updated_repeat_table as (
	select *,
	case when pt.ani_instance = 1 and pt.repeat_count > 0 then pt.repeat_count - pt.ani_instance else pt.repeat_count end as repeat_instance_count
	from
	partitioned_table pt
),
-- Set Computed Values
final_query as (
	select
	flat.[15MinsInterval],
	flat.[30MinsInterval],
	flat.[60MinsInterval],
	flat.Sunday,
	flat.Saturday,
	flat.Minus15Mins,
	flat.Minus30Mins,
	flat.Minus60Mins,
	flat.Week_count,
	flat.Day_Count,
	flat.Day_Name,
	flat.perDate,
	flat.Month_Count,
	
	flat.MONTH_NAME,
	flat.Year_Month,
	flat.Year,
	flat.DATE_TIME,
	flat.Service_Shift,
	(Sum(flat.Answered) + Sum(flat.Abandoned)) as Offered,
	Sum(flat.Answered) as Answered,
	Sum(flat.Abandoned) as Abandoned,
	Sum(flat.CallsOverFlowed) as CallsOverFlowed,
	Sum(flat.AnsweredDelay) as AnsweredDelay,
	Max(flat.AnsweredDelay) as MaxAnsweredDelay,
	Sum(flat.AnsweredDelay) as DelaySkillSet,
	Sum(flat.AnsAfterThreshold) as AnsAfterThreshold,
	Sum(flat.AnsWithinThreshold) as AnsWithinThreshold,
	Sum(flat.HandlingTime) as HandlingTime,
	isnull(Max(flat.HandlingTime), 0) as MaxHandlingTime,
	Min(flat.HandlingTime) as MinHandlingTime,
	Max(flat.AbandonDelay) as MaxAbandonDelay,
	Sum(flat.AbanAfterThreshold) as AbanAfterThreshold,
	flat.service_c,
	flat.service_id,
	flat.SiteName,
	flat.skill_id,
	Sum(flat.repeat_instance_count) as RepeatCall,
	0 as ANICount
	from
	updated_repeat_table flat --flat_table flat
	--left join handled_minmax minmax on flat.[15MinsInterval] = minmax.[15MinsInterval] and flat.SiteName = minmax.SiteName and flat.service_id = minmax.service_id
	group by
	flat.[15MinsInterval],
	flat.[30MinsInterval],
	flat.[60MinsInterval],
	flat.Sunday,
	flat.Saturday,
	flat.Minus15Mins,
	flat.Minus30Mins,
	flat.Minus60Mins,
	flat.Week_count,
	flat.Day_Count,
	flat.Day_Name,
	flat.perDate,
	flat.Month_Count,
	flat.MONTH_NAME,
	flat.Year_Month,
	flat.YEAR,
	flat.DATE_TIME,
	flat.service_id,
	flat.service_c,
	flat.SiteName,
	flat.skill_id,
	flat.Service_Shift,
	flat.HandlingTime
)
select 
	--* from final_query 
	distinct 
		SiteName
		
		--,service_id
		--,skill_id
		,f.year as year
		,replace(f.Year_Month,cast(f.year as varchar(10)) + ', ','') as month
		,month(MONTH_NAME) as month_int
		
		,round(convert(float,(convert(float,sum(AnsweredDelay))/iif(convert(float,sum(answered))=0,1,convert(float,sum(answered))))),0) as asa_sec
		,CONVERT(time(0),DATEADD(SECOND,( round(convert(float,(convert(float,sum(AnsweredDelay))/iif(convert(float,sum(answered))=0,1,convert(float,sum(answered))))),0)) ,0)) as asa_time
		--,sum(AnsweredDelay) / sum(answered) as asa
		--,CONVERT(time(0), DATEADD(SECOND,round(round(convert(float,(convert(float,sum(AnsweredDelay) / sum(answered))) ),2),0),0)) as asa_time
		,sum(handlingtime) as sum_handlingtime
		--,sum(handlingtime) / sum(answered) as aht
		,round(convert(float,(convert(float,sum(handlingtime))/convert(float,iif(sum(answered)=0,1,sum(answered))))),0) as aht_sec
		,CONVERT(time(0),DATEADD(SECOND,( round(convert(float,(convert(float,sum(handlingtime))/convert(float,iif(sum(answered)=0,1,sum(answered))))),0)) ,0)) as aht_time
		--,CONVERT(time(0), DATEADD(SECOND,round(round(convert(float,(convert(float,sum(handlingtime) / sum(answered))) ),2),0),0)) as aht_time

		,sum(offered) as sum_offered
		,sum(offered) as sum_call_registered
		,sum(answered) as sum_answered
		,sum(Abandoned) as sum_abandoned
		,sum(AnsWithinThreshold) as sum_ans_w20
		,0 as calls_offered_to_agent
		,0 as avg_no_of_agents
		
		,round(convert(float,(convert(float,sum(answered))/convert(float,iif(sum(OFFERED)=0,1,sum(OFFERED)))) * 100),2) as answer_level
		,round(convert(float,(convert(float,sum(ANSwithinThreshold))/convert(float,iif(sum(OFFERED)=0,1,sum(OFFERED)))) * 100),2) as service_level
		
		
		
from final_query f
where service_c is not null
and SiteName in (".$sitefinal.")
and service_id in (".$servicefinal.")
and skill_id in(".$skillfinal.")
group by 
	SiteName 
	--,service_id
	--	,skill_id
	,f.year
	,replace(f.Year_Month,cast(f.year as varchar(10)) + ', ','') 
	,month(MONTH_NAME)


	--,4000020,4000021,4000022,4000023,4000024,4000025,4000026,4000027,4000028,4000029,4000030,4000089

";
		//echo '<pre>' . $cmd . '</pre>';die();
		$query = $this->db->query($cmd);
		return $query;
	}


	function get_vhis19_saso($range,$dtFrom,$dtTo,$siteid,$skill,$service) {
		//echo $siteid;die();
		$siteid = rtrim($siteid,",");
		$sitearr = explode(",",$siteid);
		$sitefinal = "";
		foreach($sitearr as $key) {
			$sitefinal .= "'".$key."',";
		}
		
		$sitefinal = rtrim($sitefinal,",");
		$servicefinal = rtrim($service,",");
		
		$cmd = "DECLARE @FromDateTime datetime;
				DECLARE @ToDateTime datetime; 
				SET @FromDateTime='01/01/2019 00:00:00'; 
				SET @ToDateTime='12/31/2019 23:59:59';



				declare @tmpApplicationPerformanceV2 table (
					[15MinsInterval] datetime,
					[30MinsInterval] datetime,
					[60MinsInterval] datetime,
					Sunday varchar(50),
					Saturday varchar(50),
					Minus15Mins varchar(50),
					Minus30Mins varchar(50),
					Minus60Mins varchar(50),
					Week_count int,
					Day_Count int,
					Day_Name varchar(50),
					perDate varchar(50),
					Month_Count int,
					MONTH_NAME varchar(50),
					Year_Month varchar(50),
					YEAR int,
					DATE_TIME datetime,
					Service_Shift varchar(50),
					Offered int,
					Answered int,
					Abandoned int,
					CallsOverflowed int,
					CallDelay int,
					MaxCallDelay int,
					DelaySkillset int,
					AfterThreshold int,
					WithinThreshold int,
					HANDLING_TIME INT,
					MaxHandlingTime int,
					MinHandlingTime int,
					AbandonedDelay int,
					AbandonedAfterThreshold int,
					SERVICE_C varchar(50),
					Service_Id int,
					SiteName varchar(20),
					RepeatCalls int,
					ANICount int
				);

				-- Create Fifteen Minute Intervals
				with DateRange([15MinsInterval]) as 
				(
					select Dateadd(minute, 0, @FromDateTime)
					union all
					select Dateadd(minute, 15, [15MinsInterval])
					from DateRange  
					where Dateadd(minute, 0, [15MinsInterval]) < Dateadd(minute, -15, @ToDateTime)
				),
				-- Base Call Details
				incoming_calls as (
				select 
				distinct(acdCallDetail.SeqNum) as seqnum,
				dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, acdCallDetail.CallStartDt)) / 15 * 15, 0)) as FifteenMinutesInterval,
				acdCallDetail.CallStartDt as callstartdt,
				acdCallDetail.CallId as callid,
				acdCallDetail.CallTypeId as calltypeid,
				acdCallDetail.CallActionId as callactionid,
				acdCallDetail.CallActionReasonId as callactionreasonid,
				acdCallDetail.User_Id as [user_id],
				acdCallDetail.Station as station,
				acdCallDetail.ANI as ani,
				acdCallDetail.Service_Id as service_id,
				[service].Service_c as service_c,
				(select top 1 Skill_Id from REPDB..ASBRCallSkillDetail where Service_Id = acdCallDetail.Service_Id and SeqNum = acdCallDetail.SeqNum and Skill_Id not in (4000001, 4000002)) as skill_id,
				--asbrCallSkillDetail.Skill_Id as skill_id,
				--skills.Skill_Desc as skill_desc,
				media.Param1 as [sin],
				media.Param2 as contactnumber,
				media.Param5 as param5,
				media.Param9 as param9,
				media.Param15 as dnis,
				acdCallDetail.QueueStartDt as queuestartdt,
				acdCallDetail.QueueEndDt as queueenddt,
				acdCallDetail.WrapEndDt as wrapenddt,
				isnull(sites.SiteName, 'MOC') as sitename
				from REPDB..ACDCallDetail acdCallDetail
				left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] [service] 
					on acdCallDetail.Service_Id = [service].Service_Id
				left join REPDB..MediaDataDetail media 
					on acdCallDetail.SeqNum = media.SeqNum and acdCallDetail.callid = media.CallId
				--left join RepUIDB..Stations stations 
				--	on acdCallDetail.Station = stations.Station
				--left join RepUIDB..Sites sites 
				--	on sites.SiteGuid = stations.SiteGuid
				--left join RepUIDB..DNIS dnis on media.param15 = dnis.DNIS
				--------------------------------------------------------------------------------
				left join RepUIDB..Stations stations on acdCallDetail.Station = stations.Station
				left join RepUIDB..Sites sites on stations.SiteGuid = sites.SiteGuid -- dnis.SiteGuid = sites.SiteGuid
				--------------------------------------------------------------------------------
				--left join REPDB..AgentLoginLogout l on acdCallDetail.User_Id = l.User_Id and l.Service_Id = 0 and acdCallDetail.CallStartDt between l.LoginDt and l.LogoutDt
				--left join REPDB..AgentStateAudit s on l.User_Id = s.User_Id and dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, l.LoginDt)) / 1 * 1, 0)) = dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, s.ModifiedDt)) / 1 * 1, 0)) --DATEADD(ms, -datepart(ms, l.LoginDt), l.LoginDt) = dateadd(ms, -datepart(ms, s.Status_Start_dt), s.Status_Start_dt)
				--left join RepUIDB..Stations station on s.Station = station.Station and acdCallDetail.Station = s.Station
				--left join RepUIDB..Sites sites on station.SiteGuid = sites.SiteGuid
				--------------------------------------------------------------------------------
				where dateadd(hour, 8, acdCallDetail.CallStartDt) >= dateadd(d, 0, @FromDateTime) and  dateadd(hour, 8, acdCallDetail.CallStartDt) <= dateadd(d, 0, @ToDateTime)
				--and media.Service_Id in (4000013, 4000019, 4000021, 4000023, 4000025, 40000027, 40000029)
				--and acdCallDetail.Service_Id in ((select Service_Id from @selectedServiceIds))
				--and acdCallDetail.Service_Id in (4000013,4000018)
				--and CallActionId NOT in (14)
				),
				incoming_calls_skills as (
					select
					incoming.*,
					(select top 1 Skill_Desc from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Skills where Skill_Id = incoming.skill_id) as skill_desc
					from
					incoming_calls incoming
					--where 
					--incoming.Skill_Id in (@Skill_Ids) or (incoming.skill_id is null and incoming.callactionid = 5)
					--incoming.Skill_Id in (4000042,4000036,4000039,4000023,4000041,4000034,4000031,4000035,4000037,4000040,4000033,4000043,4000032,4000038,4000050,4000051,4000048,4000049,4000044,4000045,4000047,4000046,4000008,4000026,4000024,4000019,4000009,4000029,4000011,4000012,4000020,4000010,4000017,4000003,4000015,4000028,4000014,4000022,4000005,4000007,4000013,4000016,4000021,4000027,4000006,4000030,4000052) or (incoming.skill_id is null and incoming.callactionid = 5)
				),
				-- Get all handled from incoming calls
				handled_base as (
					select
					incoming.FifteenMinutesInterval,
					incoming.callstartdt,
					incoming.seqnum,
					incoming.callid,
					incoming.callactionid,
					incoming.[sin],
					incoming.[ani],
					incoming.service_id,
					incoming.service_c,
					incoming.skill_id,
					incoming.skill_desc,
					incoming.param5,
					incoming.param9,
					incoming.dnis,
					incoming.SiteName,
					incoming.[user_id],
					incoming.station,
					datediff(s, incoming.queuestartdt, incoming.queueenddt) as answerdelay,
					datediff(s, incoming.queuestartdt, incoming.queueenddt) as skillsetanswerdelay,
					datediff(s, incoming.queueenddt, incoming.wrapenddt) as handlingtime
					from
					incoming_calls_skills incoming
					where incoming.callactionid in (3, 8) and queuestartdt is not null
				),
				-- Get all abandoned from incoming calls
				abandoned_base as (
					select
					incoming.FifteenMinutesInterval,
					incoming.callstartdt,
					incoming.seqnum,
					incoming.callid,
					incoming.callactionid,
					incoming.[sin],
					incoming.[ani],
					incoming.service_id,
					incoming.service_c,
					incoming.skill_id,
					incoming.skill_desc,
					incoming.param5,
					incoming.param9,
					incoming.dnis,
					incoming.SiteName,
					incoming.[user_id],
					incoming.station,
					datediff(s, incoming.queuestartdt, incoming.queueenddt) as abandondelay,
					0 as skillsetabandondelay,
					0 as handlingtime
					FROM 
					incoming_calls_skills incoming
					left join RepUIDB..DNIS d on incoming.dnis = d.DNIS
					left join RepUIDB..Sites s on d.SiteGuid = s.SiteGuid
					WHERE 
					incoming.callactionid IN (5,6)
				),
				-- Get all overflowed from incoming calls
				overflowed_base as (
					select
					incoming.FifteenMinutesInterval,
					incoming.callstartdt,
					incoming.seqnum,
					incoming.callid,
					incoming.callactionid,
					incoming.[sin],
					incoming.[ani],
					incoming.service_id,
					incoming.service_c,
					incoming.skill_id,
					incoming.skill_desc,
					incoming.param5,
					incoming.param9,
					incoming.dnis,
					incoming.SiteName,
					incoming.[user_id],
					incoming.station,
					datediff(s, incoming.queuestartdt, incoming.queueenddt) as overflowdelay,
					datediff(s, incoming.queuestartdt, incoming.queueenddt) as skillsetoverflowdelay,
					0 as handlingtime
					from
					incoming_calls_skills incoming
					where
					incoming.callactionid = 18
				),
				-- Get Agent Login Site Info
				agent_site_login as (
					select
					l.LoginDt,
					l.LogoutDt,
					l.User_Id, 
					s.Station,
					sites.SiteName as agent_site,
					[service].Service_Id as service_id,
					[service].Service_c as service_c
					from
					REPDB..AgentLoginLogout l
					left join REPDB..AgentStateAudit s on l.User_Id = s.User_Id and DATEADD(ms, -datepart(ms, l.LoginDt), l.LoginDt) = dateadd(ms, -datepart(ms, s.Status_Start_dt), s.Status_Start_dt)
					-----------------------------------------------------------------------
					--dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, l.LoginDt)) / 1 * 1, 0)) = dateadd(hour, 8, dateadd(minute, datediff(minute, 0, dateadd(minute, 0, s.ModifiedDt)) / 1 * 1, 0))
					-----------------------------------------------------------------------
					left join RepUIDB..Stations station on s.Station = station.Station
					left join RepUIDB..Sites sites on station.SiteGuid = sites.SiteGuid
					left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] [service] on l.Service_Id = [service].Service_Id
					where --s.Station is not null
					s.Agent_Index is not null
					------------------------------------------------------------------------
					and (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or l.LogoutDt is null))
					------------------------------------------------------------------------
					--and (dateadd(hour, 8, l.LoginDt) between @FromDateTime and @ToDateTime or (dateadd(hour, 8, l.LoginDt) >= @FromDateTime and l.LogoutDt is null))
					------------------------------------------------------------------------
					--and l.Service_Id in ((select Service_Id from @selectedServiceIds))
					--and l.Service_Id in (4000013,4000018)
					--(4000013,4000018,4000019,4000020,4000021,4000022,4000023,4000024,4000025,4000026,4000027,4000028,4000029,4000030) 
					--(4000013, 4000019, 4000021, 4000023, 4000025, 40000027, 40000029)
				),
				-- Tag proper abandoned site
				abandoned_agent_site as (
					select
					ab.FifteenMinutesInterval,
					ab.callstartdt,
					ab.service_id,
					ab.service_c,
					null as skill_id,
					null as skill_desc,
					ab.seqnum,
					ab.callid,
					ab.callactionid,
					ab.ani,
					ab.dnis,
					ab.sitename,
					'' as station,
					isnull(agt.agent_site, 'MOC') as proper_site_tagging,
					count(distinct agt.User_Id) as number_of_online_agents,
					0 as answered_flag,
					0 as overflow_flag,
					ab.param5 as last_ivr,
					ab.[sin],
					0 as repeat_count,
					ab.abandondelay as [delay],
					ab.skillsetabandondelay as [skillsetdelay],
					ab.handlingtime as [handlingtime],
					case when ab.abandondelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold
					from
					abandoned_base ab
					left join agent_site_login agt on (ab.callstartdt between agt.LoginDt and agt.LogoutDt or ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) and ab.SiteName = agt.agent_site -- or (ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
					left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on ab.service_id = asbr.Service_Id
					group by ab.FifteenMinutesInterval, ab.service_id, ab.service_c, ab.callstartdt, ab.SeqNum, ab.callid, ab.ani, ab.dnis, ab.SiteName, agt.agent_site, ab.callactionid, ab.param5, ab.[sin], ab.abandondelay, asbr.TargetQTime, ab.skillsetabandondelay, ab.handlingtime
				),
				-- Tag proper handled site
				handled_agent_site as (
					select
					hd.FifteenMinutesInterval,
					hd.callstartdt,
					hd.service_id,
					hd.service_c,
					hd.skill_id,
					hd.skill_desc,
					hd.seqnum,
					hd.callid,
					hd.callactionid,
					hd.ani,
					hd.dnis,
					hd.sitename,
					hd.station,
					isnull(agt.agent_site, 'MOC') as proper_site_tagging,
					count(distinct agt.User_Id) as number_of_online_agents,
					1 as answered_flag,
					0 as overflow_flag,
					hd.param5 as last_ivr,
					hd.[sin],
					1 as repeat_count,
					hd.answerdelay as [delay],
					hd.skillsetanswerdelay as [skillsetdelay],
					hd.handlingtime as [handlingtime],
					case when hd.answerdelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold
					from
					handled_base hd
					left join agent_site_login agt 
						on 
							hd.[user_id] = agt.[User_Id] and
							--hd.service_id = agt.service_id and 
							--hd.station = agt.Station and
							(hd.callstartdt between agt.LoginDt and agt.LogoutDt or hd.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
					left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on hd.service_id = asbr.Service_Id
					group by hd.FifteenMinutesInterval, hd.service_id, hd.service_c, hd.skill_id, hd.skill_desc, hd.callstartdt, hd.seqnum, hd.callid, hd.ani, hd.dnis, hd.station, hd.sitename, agt.agent_site, hd.callactionid, hd.param5, hd.[sin], hd.answerdelay, asbr.TargetQTime, hd.skillsetanswerdelay, hd.handlingtime --, hd.dnis, hd.SiteName, hd.queuestartdt, hd.queueenddt, hd.wrapenddt, hd.service_id
				),
				-- Tag proper overflowed site
				overflowed_agent_site as (
					select
					ov.FifteenMinutesInterval,
					ov.callstartdt,
					ov.service_id,
					ov.service_c,
					null as skill_id,
					null as skill_desc,
					ov.seqnum,
					ov.callid,
					ov.callactionid,
					ov.ani,
					ov.dnis,
					ov.sitename,
					'' as station,
					isnull(agt.agent_site, 'MOC') as proper_site_tagging,
					count(distinct agt.User_Id) as number_of_online_agents,
					null as answered_flag,
					1 as overflow_flag,
					ov.param5 as last_ivr,
					ov.[sin],
					0 as repeat_count,
					ov.overflowdelay as [delay],
					ov.skillsetoverflowdelay as [skillsetdelay],
					ov.handlingtime as [handlingtime],
					case when ov.overflowdelay <= asbr.TargetQTime then 1 else 0 end as WinThreshold
					from
					overflowed_base ov
					left join agent_site_login agt on (ov.callstartdt between agt.LoginDt and agt.LogoutDt or ov.callstartdt >= agt.LoginDt and agt.LogoutDt is null) and ov.SiteName = agt.agent_site -- or (ab.callstartdt >= agt.LoginDt and agt.LogoutDt is null) 
					left join [VW12PCTIDB01\UIP_CONFIG].config_epro.dbo.ASBRService asbr on ov.service_id = asbr.Service_Id
					group by ov.FifteenMinutesInterval, ov.service_id, ov.service_c, ov.callstartdt, ov.SeqNum, ov.callid, ov.ani, ov.dnis, ov.SiteName, agt.agent_site, ov.callactionid, ov.param5, ov.[sin], ov.overflowdelay, asbr.TargetQTime, ov.skillsetoverflowdelay, ov.handlingtime
				),
				-- Merge Handled + Abandoned
				offered_calls as (
					select * from handled_agent_site
					union all
					select * from abandoned_agent_site
					union all
					select * from overflowed_agent_site
				),
				-- Set Flat Table Values
				flat_table as (
					select distinct
					incoming.FifteenMinutesInterval as '15MinsInterval'
					,DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30,incoming.FifteenMinutesInterval)) / 30 * 30, 0)) as '30MinsInterval'
					,DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60,incoming.FifteenMinutesInterval)) / 60 * 60, 0)) as '60MinsInterval'
					,CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval))) %7 , dateadd(hour, 0,incoming.FifteenMinutesInterval))), 101) as Sunday
					,CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval))) %7 , dateadd(hour, 0,incoming.FifteenMinutesInterval))), 101) as Saturday
					,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
						format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 15 * 15, 0)) as Datetime), 'hh:mm:ss.fff') as Minus15Mins
					,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
						format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 30 * 30, 0)) as Datetime), 'hh:mm:ss.fff') as Minus30Mins
					,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) + ' ' +
						format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,incoming.FifteenMinutesInterval)) / 60 * 60, 0)) as Datetime), 'hh:mm:ss.fff') as Minus60Mins
					,datepart(wk, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Week_count
					,datepart(w, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Day_Count
					,datename(dw, dateadd(hour, 0,incoming.FifteenMinutesInterval)) as Day_Name
					,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) as perDate
					,DATEPART(M, DATEADD(hour, 0,incoming.FifteenMinutesInterval)) AS Month_Count
					
					,cast(datename(m, dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) AS MONTH_NAME
					,cast(year(dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,incoming.FifteenMinutesInterval)) as varchar(10)) as Year_Month
					,YEAR(DATEADD(hour, 0,incoming.FifteenMinutesInterval)) AS YEAR
					,DATEADD(hour, 0,incoming.FifteenMinutesInterval) AS DATE_TIME
					,convert(varchar(10), dateadd(hour, 0,incoming.FifteenMinutesInterval), 101) +' '+
						case when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 5 or datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) >= 22 then '10PM - 6AM'
						when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) > = 14 and datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 21 then '2PM - 10PM'
						when datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) >= 6 and datepart(hour, dateadd(hour, 0, incoming.FifteenMinutesInterval)) <= 13 then '6AM - 2PM' end
						as Service_Shift
					,isnull(incoming.answered_flag, 0) as Answered
					,case when incoming.answered_flag = 0 then 1 else 0 end as Abandoned
					,isnull(incoming.overflow_flag, 0) as CallsOverFlowed
					--,isnull(incoming.delay,0) as AnsweredDelay
					,case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then isnull(incoming.delay, 0) else 0 end as AnsweredDelay
					,case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then isnull(incoming.skillsetdelay, 0) else 0 end as AnsDelayAtSkillset
					,(case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 0 then 1 else 0 end) else 0 end) as AnsAfterThreshold
					,(case when (isnull(incoming.answered_flag, 0) = 1) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 1 then 1 else 0 end) else 0 end) as AnsWithinThreshold
					,isnull(incoming.handlingtime, 0) as HandlingTime
					,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then incoming.[delay] else 0 end) as AbandonDelay
					,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (incoming.[delay] > 20) then 1  else 0 end )else 0 end) as AbanAfterThreshold
					--,(case when (isnull(incoming.answered_flag, 0) = 0) and (isnull(incoming.overflow_flag, 0) = 0) then (case when (isnull(incoming.WinThreshold, 0)) = 1 then 1 else 0 end) else 0 end) as AbanAfterThreshold
					,incoming.service_c
					,incoming.service_id
					,incoming.skill_id
					,incoming.skill_desc
					,incoming.sitename as SiteName
					,incoming.seqnum
					,incoming.callactionid
					,incoming.callstartdt
					,incoming.ani
					,incoming.repeat_count
					from 
					offered_calls incoming
				),
				-- Set instance of each ani within 24 hours
				partitioned_table as (
					SELECT *, 
						ROW_NUMBER() OVER(
						PARTITION BY flat_table.ani, flat_table.service_id, flat_table.skill_id, (DATEADD(minute, (DATEDIFF(minute, '', flat_table.callstartdt)/1440)*1440, '')) --, flat_table.skill_id
						ORDER BY flat_table.ani, flat_table.callstartdt) AS ani_instance
					FROM flat_table
				),
				-- Set Repeat Count -1 for ani_instance = 1
				updated_repeat_table as (
					select *,
					case when pt.ani_instance = 1 and pt.repeat_count > 0 then pt.repeat_count - pt.ani_instance else pt.repeat_count end as repeat_instance_count
					from
					partitioned_table pt
				),
				-- Set Computed Values
				final_query as (
					select
					flat.[15MinsInterval],
					flat.[30MinsInterval],
					flat.[60MinsInterval],
					flat.Sunday,
					flat.Saturday,
					flat.Minus15Mins,
					flat.Minus30Mins,
					flat.Minus60Mins,
					flat.Week_count,
					flat.Day_Count,
					flat.Day_Name,
					flat.perDate,
					flat.Month_Count,
					
					flat.MONTH_NAME,
					flat.Year_Month,
					flat.Year,
					flat.DATE_TIME,
					flat.Service_Shift,
					(Sum(flat.Answered) + Sum(flat.Abandoned)) as Offered,
					Sum(flat.Answered) as Answered,
					Sum(flat.Abandoned) as Abandoned,
					Sum(flat.CallsOverFlowed) as CallsOverFlowed,
					Sum(flat.AnsweredDelay) as AnsweredDelay,
					Max(flat.AnsweredDelay) as MaxAnsweredDelay,
					Sum(flat.AnsweredDelay) as DelaySkillSet,
					Sum(flat.AnsAfterThreshold) as AnsAfterThreshold,
					Sum(flat.AnsWithinThreshold) as AnsWithinThreshold,
					Sum(flat.HandlingTime) as HandlingTime,
					isnull(Max(flat.HandlingTime), 0) as MaxHandlingTime,
					Min(flat.HandlingTime) as MinHandlingTime,
					Max(flat.AbandonDelay) as MaxAbandonDelay,
					Sum(flat.AbanAfterThreshold) as AbanAfterThreshold,
					flat.service_c,
					flat.service_id,
					flat.SiteName,
					--flat.skill_id,
					isnull(flat.Skill_Id, 
									case flat.Service_Id
										when 4000013 then 4000019
										when 4000018 then 4000019
										when 4000019 then 4000023
										when 4000020 then 4000023
										when 4000027 then 4000050
										when 4000028 then 4000050
										when 4000029 then 4000051
										when 4000030 then 4000051
										when 4000021 then 4000044
										when 4000022 then 4000044
										when 4000023 then 4000049
										when 4000024 then 4000049
										when 4000025 then 4000048
										when 4000026 then 4000048
									end
					)as skill_id,
					--flat.skill_desc,
					isnull(flat.Skill_Desc, case flat.Service_Id
										when 4000013 then 'Res_Customer_Assist_sk'
										when 4000018 then 'Res_Customer_Assist_sk'
										when 4000019 then 'Biz_Customer_Assist_sk'
										when 4000020 then 'Biz_Customer_Assist_sk'
										when 4000027 then 'Cxe_Customer_Assist_sk'
										when 4000028 then 'Cxe_Customer_Assist_sk'
										when 4000029 then 'Dpa_Customer_Assist_sk'
										when 4000030 then 'Dpa_Customer_Assist_sk'
										when 4000021 then 'Pres_Customer_Assist_sk'
										when 4000022 then 'Pres_Customer_Assist_sk'
										when 4000025 then 'Gov_Customer_Assist_sk'
										when 4000026 then 'Gov_Customer_Assist_sk'
										when 4000024 then 'Kwatch_Customer_Assist_sk'
										when 4000023 then 'Kwatch_Customer_Assist_sk'
										
									end
									) as Skill_Desc,
					Sum(flat.repeat_instance_count) as RepeatCall,
					0 as ANICount
					from
					updated_repeat_table flat --flat_table flat
					--left join handled_minmax minmax on flat.[15MinsInterval] = minmax.[15MinsInterval] and flat.SiteName = minmax.SiteName and flat.service_id = minmax.service_id
					group by
					flat.[15MinsInterval],
					flat.[30MinsInterval],
					flat.[60MinsInterval],
					flat.Sunday,
					flat.Saturday,
					flat.Minus15Mins,
					flat.Minus30Mins,
					flat.Minus60Mins,
					flat.Week_count,
					flat.Day_Count,
					flat.Day_Name,
					flat.perDate,
					flat.Month_Count,
					flat.MONTH_NAME,
					flat.Year_Month,
					flat.YEAR,
					flat.DATE_TIME,
					flat.service_id,
					flat.service_c,
					flat.SiteName,
					flat.Service_Shift,
					flat.HandlingTime,
					flat.skill_id,
					flat.skill_desc
				)
				select 
					--* from final_query 
					distinct 
						SiteName
						,f.year as year
						,replace(f.Year_Month,cast(f.year as varchar(10)) + ', ','') as month
						,month(MONTH_NAME) as month_int
						
						,round(convert(float,(convert(float,sum(AnsweredDelay))/convert(float,iif(sum(answered)=0,1,sum(answered))))),0) as asa_sec
						,CONVERT(time(0),DATEADD(SECOND,( round(convert(float,(convert(float,sum(AnsweredDelay))/convert(float,iif(sum(answered)=0,1,sum(answered))))),0)) ,0)) as asa_time
						--,sum(AnsweredDelay) / sum(answered) as asa
						--,CONVERT(time(0), DATEADD(SECOND,round(round(convert(float,(convert(float,sum(AnsweredDelay) / sum(answered))) ),2),0),0)) as asa_time
						,sum(handlingtime) as sum_handlingtime
						--,sum(handlingtime) / sum(answered) as aht
						,round(convert(float,(convert(float,sum(handlingtime))/convert(float,iif(sum(answered)=0,1,sum(answered))))),0) as aht_sec
						,CONVERT(time(0),DATEADD(SECOND,( round(convert(float,(convert(float,sum(handlingtime))/convert(float,iif(sum(answered)=0,1,sum(answered))))),0)) ,0)) as aht_time
						--,CONVERT(time(0), DATEADD(SECOND,round(round(convert(float,(convert(float,sum(handlingtime) / sum(answered))) ),2),0),0)) as aht_time

						,sum(offered) as sum_offered
						,sum(offered) as sum_call_registered
						,sum(answered) as sum_answered
						,sum(Abandoned) as sum_abandoned
						,sum(AnsWithinThreshold) as sum_ans_w20
						,0 as calls_offered_to_agent
						,0 as avg_no_of_agents
						
						,round(convert(float,(convert(float,sum(answered))/convert(float,iif(sum(offered)=0,1,sum(offered)))) * 100),2) as answer_level
						,round(convert(float,(convert(float,sum(ANSwithinThreshold))/convert(float,iif(sum(offered)=0,1,sum(offered)))) * 100),2) as service_level
						,service_id
						,service_c
						,skill_id
						,skill_desc
						
				from final_query f
				where service_c is not null
				and SiteName in ('Balintawak','MOC','Sta Rosa')
				and service_id in (4000008,4000013,4000018,4000019,4000020,4000021,4000022,4000023,4000024,4000025,4000026,4000027,4000028,4000029,4000030,4000089)
				group by 
					SiteName 
					,service_id
					,service_c
					,skill_id
					,skill_desc
					,f.year
					,replace(f.Year_Month,cast(f.year as varchar(10)) + ', ','') 
					,month(MONTH_NAME)";
		echo '<pre>' . $cmd . '</pre>';die();
		$query = $this->db->query($cmd);
		return $query;
	}

}