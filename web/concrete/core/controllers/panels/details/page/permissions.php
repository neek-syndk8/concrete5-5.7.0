<?
defined('C5_EXECUTE') or die("Access Denied.");
class Concrete5_Controller_Panel_Details_Page_Permissions extends BackendInterfacePageController {

	protected $viewPath = '/system/panels/details/page/permissions/simple';

	protected function canAccess() {
		return $this->permissions->canEditPagePermissions();
	}

	public function view() {
		if (PERMISSIONS_MODEL != 'simple') {
			$this->setViewObject(new View('/system/panels/details/page/permissions/advanced'));
			$this->set('editPermissions', false);
			if ($this->page->getCollectionInheritance() == 'OVERRIDE') { 
				$this->set('editPermissions', true);
			}

		} else {

			$editAccess = array();
			$viewAccess = array();
			$c = $this->page;
				
			$pk = PagePermissionKey::getByHandle('view_page');
			$pk->setPermissionObject($c);
			$assignments = $pk->getAccessListItems();
			foreach($assignments as $asi) {
				$ae = $asi->getAccessEntityObject();
				if ($ae->getAccessEntityTypeHandle() == 'group') {
					$group = $ae->getGroupObject();
					if (is_object($group)) {
						$viewAccess[] = $group->getGroupID();
					}
				}
			}

			$pk = PermissionKey::getByHandle('edit_page_contents');
			$pk->setPermissionObject($c);
			$assignments = $pk->getAccessListItems();
			foreach($assignments as $asi) {
				$ae = $asi->getAccessEntityObject();
				if ($ae->getAccessEntityTypeHandle() == 'group') {
					$group = $ae->getGroupObject();
					if (is_object($group)) {
						$editAccess[] = $group->getGroupID();
					}
				}
			}
			
			$gl = new GroupSearch();
			$gl->sortBy('gID', 'asc');
			$groups = $gl->get();

			$this->set('editAccess', $editAccess);
			$this->set('viewAccess', $viewAccess);
			$this->set('gArray', $groups);
		}
	}
	
	public function save_simple() {
		if ($this->validateAction()) {
			$c = $this->page;
			$c->setPermissionsToManualOverride();

			$pk = PermissionKey::getByHandle('view_page');
			$pk->setPermissionObject($c);
			$pt = $pk->getPermissionAssignmentObject();
			$pt->clearPermissionAssignment();
			$pa = PermissionAccess::create($pk);
			
			if (is_array($_POST['readGID'])) {
				foreach($_POST['readGID'] as $gID) {
					$pa->addListItem(GroupPermissionAccessEntity::getOrCreate(Group::getByID($gID)));
				}
			}				
			$pt->assignPermissionAccess($pa);
			
			$editAccessEntities = array();
			if (is_array($_POST['editGID'])) {
				foreach($_POST['editGID'] as $gID) {
					$editAccessEntities[] = GroupPermissionAccessEntity::getOrCreate(Group::getByID($gID));
				}
			}
			
			$editPermissions = array(
				'view_page_versions',
				'edit_page_properties',
				'edit_page_contents',
				'edit_page_speed_settings',
				'edit_page_theme',
				'edit_page_template',
				'edit_page_permissions',
				'preview_page_as_user',
				'schedule_page_contents_guest_access',
				'delete_page',
				'delete_page_versions',
				'approve_page_versions',
				'add_subpage',
				'move_or_copy_page',
			);
			foreach($editPermissions as $pkHandle) { 
				$pk = PermissionKey::getByHandle($pkHandle);
				$pk->setPermissionObject($c);
				$pt = $pk->getPermissionAssignmentObject();
				$pt->clearPermissionAssignment();
				$pa = PermissionAccess::create($pk);
				foreach($editAccessEntities as $editObj) {
					$pa->addListItem($editObj);
				}
				$pt->assignPermissionAccess($pa);
			}

			$r = new PageEditVersionResponse();
			$r->setPage($this->page);
			$r->setMessage(t('Page permissions saved successfully.'));
			$r->outputJSON();

		}
	}


}