<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Basic variables
$name        = 'AccessAPI';
$description = 'Custom API for timetable-based access control.';
$entryURL    = 'manageCards.php'; // Required even if unused
$type        = 'Additional';
$category    = 'Admin';
$version     = '1.0.21';
$author      = 'Father Vlasie';
$url         = ''; // Optional

// Module tables & gibbonSettings entries 
$moduleTables[] = "
    CREATE TABLE `accessAPINonce` (
        `nonce` VARCHAR(64) PRIMARY KEY,
        `timestamp` INT NOT NULL
    );
";

$moduleTables[] = "
    CREATE TABLE `accessAPICard` (
        `accessAPICardID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `gibbonPersonID` INT UNSIGNED NOT NULL,
        `cardNumber` VARCHAR(255) NULL,
        `active` ENUM('Y','N') NOT NULL DEFAULT 'Y',
        `updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`accessAPICardID`),
        UNIQUE KEY `unique_card` (`cardNumber`),
        UNIQUE KEY `unique_person` (`gibbonPersonID`),
        FOREIGN KEY (`gibbonPersonID`) REFERENCES gibbonPerson(`gibbonPersonID`)
            ON DELETE CASCADE
    );
";

$gibbonSetting[] = "
    REPLACE INTO gibbonSetting (scope, name, nameDisplay, description, value)
    VALUES ('AccessAPI', 'apiKeyId', 'API Key ID', 'ID for authenticating connections by 3rd parties', 'accessapi-main-001');
";

$gibbonSetting[] = "
    REPLACE INTO gibbonSetting (scope, name, nameDisplay, description, value)
    VALUES ('AccessAPI', 'apiSecret', 'API Secret', 'HMAC Secret for authenticating connections by 3rd parties', '');
";

// Action rows
$actionRows[] = [
    'name'                      => 'AccessAPI Test Endpoint',
    'category'                  => 'API',
    'description'               => 'Test endpoint for verifying AccessAPI authentication.',
    'URLList'                   => 'test.php',
    'entryURL'                  => 'test.php',
    'menuShow'                  => 'N',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
];

// ===============================
// AccessAPI UI — Settings
// ===============================
$actionRows[] = [
    'name'                      => 'AccessAPI Settings',
    'precedence'                => '0',
    'category'                  => 'Admin',
    'description'               => 'Configure API keys and authentication settings for AccessAPI.',
    'URLList'                   => 'settings.php',
    'entryURL'                  => 'settings.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'Y',
];

// ===============================
// AccessAPI UI — Manage Cards
// ===============================
$actionRows[] = [
    'name'                      => 'AccessAPI Manage Cards',
    'precedence'                => '1',
    'category'                  => 'Admin',
    'description'               => 'Manage card registration for AccessAPI.',
    'URLList'                   => 'manageCards.php',
    'entryURL'                  => 'manageCards.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'Y',
];

// ===============================
// AccessAPI — Users List
// ===============================
$actionRows[] = [
    'name'                      => 'AccessAPI_users',
    'precedence'                => '0',
    'category'                  => 'AccessAPI',
    'description'               => 'Returns all users in JSON format.',
    'URLList'                   => 'users.php',
    'entryURL'                  => 'users.php',
    'entrySidebar'              => 'N',
    'menuShow'                  => 'N',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'Y',
];

// ===============================
// AccessAPI — User By ID
// ===============================
$actionRows[] = [
    'name'                      => 'AccessAPI_userById',
    'precedence'                => '1',
    'category'                  => 'AccessAPI',
    'description'               => 'Returns a single user by ID in JSON format.',
    'URLList'                   => 'userById.php',
    'entryURL'                  => 'userById.php',
    'entrySidebar'              => 'N',
    'menuShow'                  => 'N',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionSupport'  => 'Y',
];

// ===============================
// AccessAPI — Timetable for User
// ===============================
$actionRows[] = [
    'name'                      => 'AccessAPI_timetableUser',
    'precedence'                => '2',
    'category'                  => 'AccessAPI',
    'description'               => 'Returns today’s timetable entries for a user.',
    'URLList'                   => 'timetableUser.php',
    'entryURL'                  => 'timetableUser.php',
    'entrySidebar'              => 'N',
    'menuShow'                  => 'N',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionSupport'  => 'Y',
];

// ===============================
// AccessAPI — Access Window for User
// ===============================
$actionRows[] = [
    'name'                      => 'AccessAPI_accessUser',
    'precedence'                => '3',
    'category'                  => 'AccessAPI',
    'description'               => 'Returns computed access window for a user.',
    'URLList'                   => 'accessUser.php',
    'entryURL'                  => 'accessUser.php',
    'entrySidebar'              => 'N',
    'menuShow'                  => 'N',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionSupport'  => 'Y',
];

// ===============================
// AccessAPI — Access Now (Diagnostic)
// ===============================
$actionRows[] = [
    'name'                      => 'AccessAPI_accessNow',
    'precedence'                => '4',
    'category'                  => 'AccessAPI',
    'description'               => 'Diagnostic: shows who should have access right now.',
    'URLList'                   => 'accessNow.php',
    'entryURL'                  => 'accessNow.php',
    'entrySidebar'              => 'N',
    'menuShow'                  => 'N',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionSupport'  => 'Y',
];


// Hooks
$hooks = [];
