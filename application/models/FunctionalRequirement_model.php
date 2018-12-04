<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FunctionalRequirement_model extends CI_Model {
	function __construct(){
		parent::__construct();
	}

	function searchFunctionalRequirementHeaderInfo($param){
		if(isset($param->projectId) && !empty($param->projectId)){
			$where[] = "FRH.projectId = ".$param->projectId;
		}
		
		if(isset($param->status) && ("2" != $param->status)){
			$where[] = "FRV.activeFlag = '".$param->status."'";
		}

		if(isset($param->functionId) && !empty($param->functionId)){
			$where[] = "FRH.functionId = ".$param->functionId;
		}

		if(isset($param->functionVersion) && !empty($param->functionVersion)){
			$where[] = "FRV.functionVersionNumber = ".$param->functionVersion;
		}
		
		$where_clause = implode(' AND ', $where);
		$queryStr = "SELECT 
				FRH.functionId, 
				FRH.functionNo, 
				CAST(FRH.functionDescription AS VARBINARY(MAX)) as fnDesc, 
				FRV.functionVersionNumber as functionVersion, 
				FRV.activeFlag as functionStatus,
				CONVERT(nvarchar, FRV.effectiveStartDate , 120) as effectiveStartDate
			FROM M_FN_REQ_HEADER FRH 
			INNER JOIN M_FN_REQ_VERSION FRV 
			ON FRH.functionId = FRV.functionId 
			WHERE $where_clause 
			ORDER BY FRH.functionNo, FRV.functionVersionNumber";
		$result = $this->db->query($queryStr);
		return $result->result_array();
	}

	function searchCountAllFunctionalRequirements(){
		$result = $this->db->query("
			SELECT count(*) as counts FROM M_FN_REQ_HEADER");
		return $result->row();
	}

	function searchExistFunctionalRequirement($fnId, $projectId){

		if(null != $projectId && !empty($projectId)){
			$where[] = "projectId = '$projectId'";
		}
		if(null != $fnId && !empty($fnId)){
			$where[] = "functionNo = '$fnId'";
		}
		$where_clause = implode(' AND ', $where);
		
		$queryStr = "SELECT * 
			FROM M_FN_REQ_HEADER 
			WHERE $where_clause";
		//echo $queryStr;
		$result = $this->db->query($queryStr);
		return $result->result_array();
	}

	function searchFRInputInformation($projectId, $inputName, $activeFlag){
		$activeFlag = (!empty($activeFlag)? "'".$activeFlag."'": "NULL");
		$queryStr = "SELECT * 
			FROM M_FN_REQ_INPUT i 
			WHERE i.projectId = $projectId 
			AND i.inputName = '$inputName'
			AND ($activeFlag is null or i.activeFlag = $activeFlag)
			ORDER BY i.createDate desc";
		$result = $this->db->query($queryStr);
		return $result->row();
	}

	function searchFRInputInfoByInputId($inputId){
		$queryStr = "SELECT * 
			FROM M_FN_REQ_INPUT i 
			WHERE i.inputId = $inputId";
		$result = $this->db->query($queryStr);
		return $result->row();
	}

	function searchFRInputDetailByCriteria($param){
		if(null != $param->inputId && !empty($param->inputId)){
			$where[] = "i.inputId = $param->inputId";
		}

		if(null != $param->schemaVersionId && !empty($param->schemaVersionId)){
			$where[] = "d.schemaVersionId = $param->schemaVersionId";
		}
		$where_clause = implode(' AND ', $where);

		$sqlStr = "
			SELECT i.inputName, i.refTableName, i.refColumnName, d.dataType, d.dataLength, d.decimalPoint, d.constraintUnique, d.constraintNull, d.constraintDefault, d.constraintMinValue, d.constraintMaxValue
			FROM M_FN_REQ_INPUT i
			INNER JOIN M_DATABASE_SCHEMA_INFO d
			ON i.refTableName = d.tableName
			AND i.refColumnName = d.columnName
			WHERE $where_clause";

		$result = $this->db->query($sqlStr);
		return $result->row_array();
	}

	function searchExistFRInputInFunctionalRequirement($param){
		if(isset($param->functionId) && !empty($param->functionId)){
			$where[] = "h.functionId = $param->functionId";
		}
		if(isset($param->inputName) && !empty($param->inputName)){
			$where[] = "i.inputName = '$param->inputName'";
		}
		if(isset($param->inputId) && !empty($param->inputId)){
			$where[] = "i.inputId = $param->inputId";
		}
		if(isset($param->schemaVersionId) && !empty($param->schemaVersionId)){
			$where[] = "d.schemaVersionId = $param->schemaVersionId";
		}
		if(isset($param->inputActiveFlag) && !empty($param->inputActiveFlag)){
			$where[] = "i.activeFlag = '$param->inputActiveFlag'";
		}

		$where_clause = implode(' AND ', $where);

		$queryStr = "SELECT h.functionId, d.inputId, i.inputName, d.schemaVersionId
			FROM M_FN_REQ_HEADER h
			INNER JOIN M_FN_REQ_DETAIL d
			ON h.functionId = d.functionId
			AND d.activeFlag = '1'
			INNER JOIN M_FN_REQ_INPUT i
			ON d.inputId = i.inputId
			WHERE $where_clause";

		$result = $this->db->query($queryStr);
		return $result->result_array();
	}

	function searchExistFRDetailbyCriteria($param){
		$sqlStr = "SELECT *
			FROM M_FN_REQ_DETAIL
			WHERE functionId = $param->functionId
			AND inputId = $param->inputId
			AND effectiveStartDate = '$param->effectiveStartDate'";
		$result = $this->db->query($sqlStr);
		return $result->result_array();
	}

	function searchExistFRInputsByTableAndColumnName($tableName, $columnName, $projectId, $activeFlag){
		$activeFlag = (!empty($activeFlag)? "'".$activeFlag."'": "NULL");
		$queryStr = "SELECT *
			FROM M_FN_REQ_INPUT fi
			WHERE fi.refTableName = '$tableName'
			AND fi.refColumnName = '$columnName'
			AND fi.projectId = $projectId
			AND ($activeFlag is null or fi.activeFlag = $activeFlag)";
		$result = $this->db->query($queryStr);
		return $result->row();
	}

	function searchReferenceDatabaseSchemaInfo($param){

		if(isset($param->projectId) && !empty($param->projectId)){
			$where[] = "di.projectId = $param->projectId";
		}

		if(isset($param->referTableName) && !empty($param->referTableName)){
			$where[] = "di.tableName = '$param->referTableName'";
		}

		if(isset($param->referColumnName) && !empty($param->referColumnName)){
			$where[] = "di.columnName = '$param->referColumnName'";
		}

		$where_clause = implode(' AND ', $where);

		$queryStr = "SELECT di.*
			FROM M_DATABASE_SCHEMA_VERSION dv
			INNER JOIN M_DATABASE_SCHEMA_INFO di
			ON dv.schemaVersionId = di.schemaVersionId
			WHERE dv.activeFlag = '1'
			AND $where_clause";
		$result = $this->db->query($queryStr);
		return $result->row();
	}

	function searchFunctionalRequirementDetail($param){
		if(isset($param->projectId) && !empty($param->projectId)){
			$where[] = "h.projectId = $param->projectId";
		}

		if(isset($param->functionId) && !empty($param->functionId)){
			$where[] = "h.functionId = $param->functionId";
		}

		if(isset($param->inputId) && !empty($param->inputId)){
			$where[] = "d.inputId = $param->inputId";
		}

		if(isset($param->schemaVersionId) && !empty($param->schemaVersionId)){
			$where[] = "d.schemaVersionId = $param->schemaVersionId";
		}
		$where_clause = implode(' AND ', $where);

		$queryStr = "SELECT 
				h.projectId,
				h.functionId,
				h.functionNo,
				h.functionDescription,
				v.functionVersionNumber,
				d.inputId,
				d.schemaVersionId,
				i.inputName,
				db.tableName,
				db.columnName,
				db.dataType,
				db.dataLength,
				db.decimalPoint,
				db.constraintUnique,
				db.constraintNull,
				db.constraintDefault,
				db.constraintMinValue,
				db.constraintMaxValue
			FROM M_FN_REQ_HEADER h
			INNER JOIN M_FN_REQ_VERSION v
			ON h.functionId = v.functionId
			AND v.activeFlag = '1'
			INNER JOIN M_FN_REQ_DETAIL d
			ON h.functionId = d.functionId
			AND d.activeFlag = '1'
			INNER JOIN M_FN_REQ_INPUT i
			ON d.inputId = i.inputId
			INNER JOIN M_DATABASE_SCHEMA_INFO db
			ON i.refTableName = db.tableName
			AND i.refColumnName = db.columnName
			AND d.schemaVersionId = db.schemaVersionId
			WHERE $where_clause
			ORDER BY h.functionNo";
			//var_dump($queryStr);
		$result = $this->db->query($queryStr);
		return $result->result_array();
	}

	function searchLatestFunctionalRequirementVersion($functionId, $functionVersion){
		$sqlStr = "SELECT *
			FROM M_FN_REQ_VERSION 
			WHERE functionId = $functionId
			AND functionVersionNumber = $functionVersion
			AND activeFlag = '1'" ;

		$result = $this->db->query($sqlStr);
		return $result->row();
	}

	function searchFunctionalRequirementVersionByCriteria($param){
		if(null != $param->functionId && !empty($param->functionId)){
			$where[] = "functionId = $param->functionId";
		}

		if(isset($param->functionVersionId) && !empty($param->functionVersionId)){
			$where[] = "functionVersionId = $param->functionVersionId";
		}

		if(isset($param->functionVersionNumber) && !empty($param->functionVersionNumber)){
			$where[] = "functionVersionNumber = $param->functionVersionNumber";
		}

		$where_condition = implode(" AND ", $where);

		$sqlStr = "SELECT * 
			FROM M_FN_REQ_VERSION
			WHERE $where_condition";
		$result = $this->db->query($sqlStr);
		return $result->row();
	}

	function insertFRHeader($param){
		$currentDateTime = date('Y-m-d H:i:s');
		$sqlStr = "INSERT INTO M_FN_REQ_HEADER (functionNo, functionDescription, projectId, createDate, createUser, updateDate, updateUser) VALUES ('{$param->functionNo}', '{$param->functionDescription}', {$param->projectId}, '$currentDateTime', '{$param->user}', '$currentDateTime', '{$param->user}')";
		$result = $this->db->query($sqlStr);
		if($result){
			$query = $this->db->query("SELECT IDENT_CURRENT('M_FN_REQ_HEADER') as last_id");
			$resultId = $query->result();
			return $resultId[0]->last_id;
		}
		return NULL;
	}

	function insertFRVersion($param){
		$currentDateTime = date('Y-m-d H:i:s');
		$previousVersionId = !empty($param->previousVersionId)? $param->previousVersionId : "NULL";
		$sqlStr ="INSERT INTO M_FN_REQ_VERSION (functionId, functionVersionNumber, effectiveStartDate, effectiveEndDate, activeFlag, previousVersionId, createDate, createUser, updateDate, updateUser) VALUES ($param->functionId, $param->functionVersionNo, '$param->effectiveStartDate', NULL, '$param->activeFlag', $previousVersionId, '$currentDateTime', '$param->user', '$currentDateTime', '$param->user')";

		$result = $this->db->query($sqlStr);
		if($result){
			$query = $this->db->query("SELECT IDENT_CURRENT('M_FN_REQ_VERSION') as last_id");
			$resultId = $query->result();
			return $resultId[0]->last_id;
		}
		return NULL;
	}

	function insertFRInput($param){
		$currentDateTime = date('Y-m-d H:i:s');
		$sqlStr = "INSERT INTO M_FN_REQ_INPUT (projectId, inputName, refTableName, refColumnName, createDate, createUser, updateDate, updateUser) VALUES ({$param->projectId}, '{$param->inputName}', '{$param->referTableName}', '{$param->referColumnName}', '$currentDateTime', '{$param->user}', '$currentDateTime', '{$param->user}')";
		//var_dump($sqlStr);
		$result = $this->db->query($sqlStr);
		if($result){
			$query = $this->db->query("SELECT IDENT_CURRENT('M_FN_REQ_INPUT') as last_id");
			$resultId = $query->result();
			return $resultId[0]->last_id;
		}
		return NULL;
	}

	function insertFRDetail($functionId, $param){
		$currentDateTime = date('Y-m-d H:i:s');
		$sqlStr = "INSERT INTO M_FN_REQ_DETAIL (functionId, inputId, schemaVersionId, effectiveStartDate, effectiveEndDate, activeFlag, createDate, createUser, updateDate, updateUser) VALUES ($functionId, $param->inputId, $param->schemaVersionId, '$param->effectiveStartDate', NULL, '$param->activeFlag', '$currentDateTime', '$param->user', '$currentDateTime', '$param->user')";
		$result = $this->db->query($sqlStr);
		return $result;
	}

	function updateFunctionalRequirementsVersion($param){
		$effectiveEndDate = !empty($param->effectiveEndDate)? "'".$param->effectiveEndDate."'": "NULL";

		$sqlstr = "UPDATE M_FN_REQ_VERSION 
			SET effectiveEndDate = $effectiveEndDate, 
				activeFlag = '$param->activeFlag', 
				updateDate = '$param->currentDate', 
				updateUser = '$param->user' 
			WHERE functionVersionId = $param->oldFunctionVersionId 
			AND functionId = $param->functionId 
			AND updateDate = '$param->oldUpdateDate'";

		$result = $this->db->query($sqlstr);
		return $this->db->affected_rows();
	}

	function updateStatusFRInput($param){
		$sqlStr = "UPDATE M_FN_REQ_INPUT
			SET activeFlag = '$param->activeFlag',
				updateDate = '$param->updateDate',
				updateUser = '$param->updateUser'
			WHERE projectId = $param->projectId
			AND inputId = $param->inputId";
		$result = $this->db->query($sqlStr);
		return $this->db->affected_rows();
	}

	function updateFunctionalRequirementsDetail($param){

		$effectiveEndDate = !empty($param->effectiveEndDate)? "'".$param->effectiveEndDate."'" : "NULL";

		if(isset($param->functionId) && !empty($param->functionId)){
			$where[] = "functionId = $param->functionId";
		}

		if(isset($param->inputId) && !empty($param->inputId)){
			$where[] = "inputId = $param->inputId";
		}

		if(isset($param->oldSchemaVersionId) && !empty($param->oldSchemaVersionId)){
			$where[] = "schemaVersionId = $param->oldSchemaVersionId";
		}

		if(isset($param->endDateCondition) && !empty($param->endDateCondition)){
			$where[] = "effectiveEndDate = '$param->endDateCondition'";
		}
		$where_condition = implode(" AND ", $where);

		$sqlStr = "UPDATE M_FN_REQ_DETAIL
			SET effectiveEndDate = $effectiveEndDate,
				activeFlag = '$param->activeFlag',
				updateDate = '$param->currentDate',
				updateUser = '$param->user'
			WHERE $where_condition";
		
		$result = $this->db->query($sqlStr);
		return $this->db->affected_rows();
	}

	function deleteFunctionalRequirementHeader($param){
		$sqlStr = "DELETE FROM M_FN_REQ_VERSION
			WHERE functionId = $param->functionId
			AND functionVersionId = $param->functionVersionId";

		$result = $this->db->query($sqlStr);
		return $this->db->affected_rows();
	}

	function deleteFunctionalRequirementDetail($param){
		$sqlStr = "DELETE FROM M_FN_REQ_DETAIL
			WHERE functionId = $param->functionId
			AND inputId = $param->inputId
			AND effectiveStartDate = '$param->effectiveStartDate'";

		$result = $this->db->query($sqlStr);
		return $this->db->affected_rows();
	}

	function deleteFunctionalRequirementInput($param){
		$sqlStr = "DELETE FROM M_FN_REQ_INPUT
			WHERE inputId = $param->inputId
			AND activeFlag = '1'";

		$result = $this->db->query($sqlStr);
		return $this->db->affected_rows();
	}

	function uploadFR($param){
		$this->db->trans_begin(); //Starting Transaction

		//insert Functional Requirement Header
		$functionId = $this->insertFRHeader($param[0]);
		if(NULL != $functionId){
			$effectiveStartDate = date('Y-m-d H:i:s');
			
			$headerData = (object) array(
				'functionId' => $functionId, 
				'functionVersionNo' => $param[0]->functionVersionNo, 
				'effectiveStartDate' => $effectiveStartDate,
				'effectiveEndDate' => "NULL",
				'activeFlag' => $param[0]->activeFlag,
				'user' => $param[0]->user);

			//insert Functional Requirement Version
			$resultInsertVersion = $this->insertFRVersion($headerData);

			//insert Functional Requirement Detail
			foreach ($param as $detail) {
				$inputId = '';
				//Check Exist Input
				if(empty($detail->inputId)){
					//Insert New Input
					$inputId = $this->insertFRInput($detail);
					$detail->inputId = $inputId;
				}

				$resultSchemaInfo = $this->searchReferenceDatabaseSchemaInfo($detail);

				$detail->schemaVersionId = $resultSchemaInfo->schemaVersionId;
				$detail->effectiveStartDate = $effectiveStartDate;
				$detail->effectiveEndDate = "NULL";

				$resultInsertDetail = $this->insertFRDetail($functionId, $detail);
			}// end foreach
		}// end if

    	$trans_status = $this->db->trans_status();
	    if($trans_status == FALSE){
	    	$this->db->trans_rollback();
	    	return false;
	    }else{
	   		$this->db->trans_commit();
	   		return true;
	    }
	}
}

?>