<?php

require_once 'CRM/Core/Page.php';

class CRM_Civicpd_Page_Categories extends CRM_Core_Page {
  function run() {

        CRM_Core_Resources::singleton()->addStyleFile('ca.lunahost.civicpd', 'civicpd.css');
        CRM_Core_Resources::singleton()->addScriptFile('ca.lunahost.civicpd', 'js/category.js');
        
        if (isset($_GET['action']) || isset($_POST['action'])) {

            $action = $_REQUEST['action'];

            switch ($action) {
                case 'insert':
                    if(!empty($_POST['category']) &&  civi_cpd_category_validate_number($_POST['minimum']) && !empty($_POST['description'])) {        
                        $sql = "INSERT INTO civi_cpd_categories(category, " . 
                            "description, minimum) VALUES('" . $_POST['category'] . 
                            "','" . $_POST['description'] . "','" . $_POST['minimum'] . 
                            "')";
                        
                        CRM_Core_DAO::executeQuery($sql);

                        civi_cpd_cateogry_set_add_cateogry_response(TRUE);
                    } else {
                        civi_cpd_cateogry_set_add_cateogry_response(FALSE);
                    }
                    break;

                case 'update':
                    if(isset($_POST['catid'])){
                        $catid = $_POST['catid'];
                        $category = mysql_real_escape_string($_POST['category']);
                        $description = mysql_real_escape_string($_POST['description']);
                        $minimum = $_POST['minimum'];
                        $sql = "UPDATE civi_cpd_categories SET category = '" . $category .
                            "', description = '" . $description . "', minimum = '" .
                            $minimum . "' WHERE id =" . $catid;
                        CRM_Core_DAO::executeQuery($sql); 
                    }

                    break;

                case 'delete':
                    if (isset($_GET['id'])) {
                        $sql = "DELETE FROM civi_cpd_categories WHERE id =" . $_GET['id'];
                        CRM_Core_DAO::executeQuery($sql);
                    }

                    break;

                default:
                    break;
            }
        }

    $sql = "SELECT * FROM civi_cpd_categories";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $categories = "";
  	
    while( $dao->fetch( ) ) {
        
        $edit_url = CRM_Utils_System::url("civicrm/civicpd/EditCategories", "id=" . $dao->id, true, null, false, true);
        $delete_url = CRM_Utils_System::url("civicrm/civicpd/categories", "action=delete&id=" . $dao->id, true, null, false, true);
        
        $categories .= "<tr>"
            . "<td nowrap=nowrap><strong>" . $dao->category . "</strong></td>" 
            . "<td><em>" . $dao->description . "</em></td>"  
            . "<td style='text-align: center;'>" . $dao->minimum . "</td>" 
            . "<td style='text-align: center;'><a href='" . $edit_url . "'>edit</a><a href='" . $delete_url . "'> | delete</a></td></tr>";
     }
     
    $sql = "SELECT * FROM civi_cpd_defaults";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $arr_defaults = array();
    $x = 0;
    
    while ($dao->fetch()) {   
       	$arr_defaults[$dao->name] = $dao->value;
       	$x++;	
    }
    
    if (is_array ($arr_defaults)) {
    	
    	if (isset($arr_defaults['long_name'])) {
            $long_name = $arr_defaults['long_name'];
    	} else {
            $long_name = 'Continuing Professional Development';
    	}
    	
    	if (isset($arr_defaults['short_name'])) {
            $short_name = $arr_defaults['short_name'];
    	} else {
            $short_name = 'CPD';
    	}
    	
    } else {
        $long_name = 'Continuing Professional Development';
        $short_name = 'CPD';
    }

    CRM_Utils_System::setTitle(ts('Review ' . $short_name . ' Categories'));
    $this->assign('categories', $categories);
    
    $reponse = civi_cpd_cateogry_get_add_cateogry_response();
    
    if (!empty($reponse)) {
        $this->assign('add-response', $reponse);
    }
    
    parent::run();
  }
}

function civi_cpd_cateogry_set_add_cateogry_response($success = FALSE) {
   if ($success) {
        $message = 'Category has been successfully added';
        $class = 'success';
   } else {
       $message = 'Category has failed to be added';
       $class = 'failure'; 
   }
   
   $_SESSION['civi_crm_category']['add_response'] = '<div class="add-category-response ' . 
        $class . '">' . $message . '</div>';
}

function civi_cpd_cateogry_get_add_cateogry_response() {
    return isset($_SESSION['civi_crm_category']['add_response']) ? 
        $_SESSION['civi_crm_category']['add_response'] : NULL;
}
    
function civi_cpd_category_validate_number($var) {
    if (is_numeric($var)) {
        return TRUE;
    }
}