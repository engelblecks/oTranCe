<?php
/**
 * @group x
 * @group Models
 * @group User
 */
class UserTest extends ControllerTestCase
{
    /**
     * @var \Application_Model_User
     */
    protected $userModel;

    public function setUp()
    {
        $this->userModel = new Application_Model_User();
    }

    public function testCanGetUserId()
    {
        $this->loginUser();
        $this->userModel = new Application_Model_User();
        $this->assertEquals(2, $this->userModel->getUserId());

        $this->clearRequest();
        $this->loginUser('Admin', 'admin');
        $this->userModel = new Application_Model_User();
        $this->assertEquals(1, $this->userModel->getUserId());
    }

    public function testCanGetTranslatorsOfALanguage()
    {
        $this->userModel = new Application_Model_User();
        $translators = $this->userModel->getTranslators(1);
        // we expect an array(languageId => array(userId1, userId2))
        // admin (1) and tester(2) do have edit rights for id 1 (Deutsch)
        $expected = array(1 => array(1,2));
        $this->assertEquals($expected, $translators);

        $translators = $this->userModel->getTranslators(2);
        // only admin (1) does have edit rights for id 2 (Englisch)
        $expected = array(2 => array(1));
        $this->assertEquals($expected, $translators);
    }

    public function testGetTranslatorsReturnsEmptyArrayForNonExistentLanguage()
    {
        $translators = $this->userModel->getTranslators(9999999);
        $this->assertEquals(array(), $translators);
    }

    public function testGetTranslatorListCanImplodeList()
    {
        $translatorList = $this->userModel->getTranslatorlist(true);
        $expected = array( 1=> 'Admin, tester', 2=> 'Admin');
        $this->assertEquals($expected, $translatorList);
    }

    public function testGetUsers()
    {
        // test filter
        $userList = $this->userModel->getUsers('Ad');
        $expected = array(1 => array(
                'id'       => 1,
                'username' => 'Admin',
                'password' => '21232f297a57a5a743894a0e4a801fc3',
                'active'   => 1
            )
        );
        $this->assertEquals($expected, $userList);

        // test pagination
        $userList = $this->userModel->getUsers('', 1, 1);
        $expected = array(2 => array(
                'id'       => '2',
                'username' => 'tester',
                'password' => '098f6bcd4621d373cade4e832627b4f6',
                'active'   => 1
            )
        );
        $this->assertEquals($expected, $userList);
    }

    public function testGetUserNames()
    {
        $userNames = $this->userModel->getUserNames();
        $expected = array( 1 => 'Admin', 2 => 'tester');
        $this->assertEquals($expected, $userNames);
    }

    public function testGetUserById()
    {
        //get id 1 = Admin
        $user = $this->userModel->getUserById(1);
        $expected = array(
            'id'       => 1,
            'username' => 'Admin',
            'password' => '21232f297a57a5a743894a0e4a801fc3',
            'active'   => 1
        );
        $this->assertEquals($expected, $user);

        // get id 2 = tester
        $user = $this->userModel->getUserById(2);
        $expected = array(
            'id'       => '2',
            'username' => 'tester',
            'password' => '098f6bcd4621d373cade4e832627b4f6',
            'active'   => 1
        );
        $this->assertEquals($expected, $user);

        // request invalid user and get default array
        $user = $this->userModel->getUserById(99999999);
        $expected = array(
            'id'       => '0',
            'username' => '',
            'password' => '',
            'active'   => 0
        );
        $this->assertEquals($expected, $user);
    }

    public function testGetUserByName()
    {
        $user = $this->userModel->getUserByName('Admin');
        $expected = array(
            'id'       => 1,
            'username' => 'Admin',
            'password' => '21232f297a57a5a743894a0e4a801fc3',
            'active'   => 1
        );
        $this->assertEquals($expected, $user);
    }

    public function testGetRowCount()
    {
        $users = $this->userModel->getUserNames();
        $rowCount = $this->userModel->getRowCount();
        $this->assertEquals(sizeof($users), $rowCount);
    }

    public function testLoadSetting()
    {
        $this->loginUser('Admin', 'admin');
        $this->userModel = new Application_Model_User();
        $referenceLanguages = $this->userModel->loadSetting('referenceLanguage');
        $expected = array(
            0 => '1',
            1 => '3'
        );
        $this->assertEquals($expected, $referenceLanguages);

        // check loading of one value
        $setting = $this->userModel->loadSetting('recordsPerPage');
        $this->assertEquals(30, $setting);

        // test force returning as array
        $setting = $this->userModel->loadSetting('recordsPerPage', '', true);
        $this->assertEquals(array(0 => 30), $setting);
    }

    public function testGetRefLanguages()
    {
        $this->loginUser('Admin', 'admin');
        $this->userModel = new Application_Model_User();
        $referenceLanguages = $this->userModel->getRefLanguages();
        $expected = array(
            0 => '1',
            1 => '3'
        );
        $this->assertEquals($expected, $referenceLanguages);
    }

   public function testGetReferenceLanguageStatus()
    {
        // check for set reference language
        $referenceLanguageStatus = $this->userModel->getReferenceLanguageStatus(1, 1);
        $this->assertEquals(1, $referenceLanguageStatus);

        // check for unset reference language
        $referenceLanguageStatus = $this->userModel->getReferenceLanguageStatus(1, 99999);
        $this->assertEquals(0, $referenceLanguageStatus);
    }

    /**
     * @depends testGetReferenceLanguageStatus
     */
    public function testSwitchReferenceLanguageStatus()
    {
        $this->userModel->switchReferenceLanguageStatus(1, 1);
        $referenceLanguageStatus = $this->userModel->getReferenceLanguageStatus(1, 1);
        $this->assertEquals(0, $referenceLanguageStatus);

        $this->userModel->switchReferenceLanguageStatus(1, 1);
        $referenceLanguageStatus = $this->userModel->getReferenceLanguageStatus(1, 1);
        $this->assertEquals(1, $referenceLanguageStatus);
    }

    public function testSaveSetting()
    {
        $this->loginUser('Admin', 'admin');
        $this->userModel = new Application_Model_User();
        $this->userModel->saveSetting('foo', 'bar');
        $setting = $this->userModel->loadSetting('foo');
        $this->assertEquals('bar', $setting);

        // check if true is returned on empty value (nothing to do)
        $res = $this->userModel->saveSetting('foo', '');
        $this->assertTrue($res);
    }

    /**
     * @depends testSaveSetting
     */
    public function testDeleteSetting()
    {
        $this->loginUser('Admin', 'admin');
        $this->userModel = new Application_Model_User();
        $this->userModel->deleteSetting('foo');
        $setting = $this->userModel->loadSetting('foo', 'notFound');
        $this->assertEquals('notFound', $setting);
    }

    public function testAddUsersEditLanguageRight()
    {
        $this->userModel->deleteUsersEditLanguageRight(1, 9999);
        $saved = $this->userModel->addUsersEditLanguageRight(1, 9999);
        $this->assertTrue($saved);
    }

    /**
     * @depends testAddUsersEditLanguageRight
     */
    public function testDeleteUsersEditLanguageRight()
    {
        $deleted = $this->userModel->deleteUsersEditLanguageRight(1, 9999);
        $this->assertTrue($deleted);
    }

    public function testSaveLanguageRights()
    {
        $languageIds = array(1,2,3,4,5,9999);
        $saved = $this->userModel->saveLanguageRights(99, $languageIds);
        $this->assertTrue($saved);

        $saved = $this->userModel->saveLanguageRights(99, '');
        $this->assertTrue($saved);

        // check that all rights for user 99 have been removed
        $editRights = $this->userModel->getUserLanguageRights(99);
        $this->assertEquals(array(), $editRights);

        // check that method returns false on invalid userId
        $saved = $this->userModel->saveLanguageRights(-1, '');
        $this->assertFalse($saved);
    }

    public function testGetUserGlobalRights()
    {
        $rights = $this->userModel->getUserGlobalRights(1);
        // check some rights (these are not all)
        $this->assertTrue($rights['editConfig'] == 1);
        $this->assertTrue($rights['showEntries'] == 1);
        $this->assertTrue($rights['showDownloads'] == 1);
        $this->assertTrue($rights['addVar'] == 1);
        $this->assertTrue($rights['editProject'] == 1);
        $this->assertTrue($rights['addUser'] == 1);
        $this->assertTrue($rights['createFile'] == 1);
        $this->assertTrue($rights['export'] == 1);

        // check that userId is taken from the logged inuser, if param is missing
        $this->loginUser(); // logs in test user
        $this->userModel = new Application_Model_User();
        $rights = $this->userModel->getUserGlobalRights();
        $this->assertTrue($rights['admin'] == 0);
        $this->assertTrue($rights['addVar'] == 0);
        $this->assertTrue($rights['addTemplate'] == 0);
    }

    public function testHasLanguageEditRight()
    {
        // positive check
        $hasRight = $this->userModel->hasLanguageEditRight(1, 1);
        $this->assertTrue($hasRight);

         // negative check
         $hasRight = $this->userModel->hasLanguageEditRight(1, 3246345);
         $this->assertFalse($hasRight);
    }

    public function testSaveRight()
    {
        $saved = $this->userModel->saveRight(99, 'foo', 999);
        $this->assertTrue($saved);
        $userRights = $this->userModel->getUserRights(99);
        $this->assertTrue($userRights['foo'] == 999);
    }

    /**
     * @depends testSaveRight
     */
    public function testDeleteRight()
    {
        $this->userModel->deleteRight(99, 'foo');
        $userRights = $this->userModel->getUserRights(99);
        $this->assertFalse(isset($userRights['foo']));
    }

    public function testSaveAccount()
    {
        $user = array(
            'id'       => 0,
            'username' => 'phpUnitTestUser',
            'pass1'    => 'IamKarl',
            'active'   => 1
        );
        $newId = $this->userModel->saveAccount($user);
        $this->assertTrue($newId !== false);
        $check = $this->userModel->getUserByName('phpUnitTestUser');
        $this->assertTrue($user['username'] == $check['username']);
        $this->assertTrue($user['active'] == $check['active']);

        // check update
        $user = array(
            'id' => $newId,
            'username' => 'phpUnitTestUser2',
            'pass1'    => 'I have been karl',
            'active'   => 0
        );
        $userId = $this->userModel->saveAccount($user);
        $this->assertEquals($newId, $userId);
        $this->assertTrue($user['username'] == 'phpUnitTestUser2');
        $this->assertTrue($user['active'] == 0);
        $this->userModel->deleteUserById($newId);
    }

    public function testChangePassword()
    {
        // create a test account
        $user = array(
            'id'       => 0,
            'username' => 'phpUnitTestUser',
            'pass1'    => 'IamKarl',
            'active'   => 1
        );
        $newId = $this->userModel->saveAccount($user);
        $this->assertTrue($newId !== false);

        $this->loginUser('phpUnitTestUser', 'IamKarl');
        $this->userModel = new Application_Model_User();

        $changed = $this->userModel->changePassword('IamKarl', 'IwasKarl');
        $this->assertTrue($changed);

        //check that password is changed
        $check = $this->userModel->getUserByName('phpUnitTestUser');
        $this->assertEquals(md5('IwasKarl'), $check['password']);

        $this->userModel->deleteUserById($newId);

        // ngetive check - try to change a password with wrong old passwort
        $changed = $this->userModel->changePassword('IamTotallyWrong', 'IwasKarl');
        $this->assertFalse($changed);
    }

    public function testDeleteReferenceLanguageSetting()
    {
        //log in as tester
        $this->loginUser();
        $this->userModel = new Application_Model_User();
        // save setting
        $this->userModel->saveSetting('referenceLanguage', 99);
        $refLang = $this->userModel->loadSetting('referenceLanguage');
        //check it is saved correctly
        $this->assertEquals(99, $refLang);

        // delete this setting for user with id 2 = tester
        $deleted = $this->userModel->deleteReferenceLanguageSettings(99, 2);
        $this->assertTrue($deleted);
        // now check it reallly is deleted for this user
        $refLang = $this->userModel->loadSetting('referenceLanguage');
        $this->assertEquals('', $refLang);
    }

    public function testDeleteLanguageRights()
    {
        // add edit rigth for fake language 99 for user "tester"
        $added = $this->userModel->addUsersEditLanguageRight(99, 2);
        $this->assertTrue($added);

        // delete language right for language 99 for all users
        $deleted = $this->userModel->deleteLanguageRights(99);
        $this->assertTrue($deleted);

        // make sure language 99 is no longer assigned to user "tester"
        $editRights = $this->userModel->getUserLanguageRights(2);
        $this->assertTrue(!in_array(99, $editRights));
    }
}