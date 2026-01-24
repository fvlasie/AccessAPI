<?php
namespace Gibbon\Module\AccessAPI\Services;

class UserService
{
    protected $db;

    public function __construct($container)
    {
        // Use the global container, not the module container
        global $container;

        $this->db = $container->get('db');
    }

    public function getUsers()
    {
        $sql = "
            SELECT 
                p.gibbonPersonID AS id,
                p.preferredName AS firstName,
                p.surname AS lastName,
                p.email,
                p.username,
                p.gender,
                p.status,
                r.name AS roleName,
                c.cardNumber AS idCard
            FROM gibbonPerson p
            LEFT JOIN gibbonRole r
                ON p.gibbonRoleIDPrimary = r.gibbonRoleID
            LEFT JOIN accessAPICard c
                ON c.gibbonPersonID = p.gibbonPersonID
                AND c.active = 'Y';
        ";

        return $this->db->select($sql)->fetchAll();
    }

    public function getUserById($id)
    {
        $sql = "
            SELECT 
                p.gibbonPersonID AS id,
                p.preferredName AS firstName,
                p.surname AS lastName,
                p.email,
                p.username,
                p.gender,
                p.status,
                r.name AS roleName,
                c.cardNumber AS idCard
            FROM gibbonPerson p
            LEFT JOIN gibbonRole r
                ON p.gibbonRoleIDPrimary = r.gibbonRoleID
            LEFT JOIN accessAPICard c
                ON c.gibbonPersonID = p.gibbonPersonID
                AND c.active = 'Y';
            WHERE p.gibbonPersonID = :id
            LIMIT 1
        ";

        return $this->db->select($sql, ['id' => $id])->fetch();
    }
        
public function getTimetableForUser($id, $date)
    {
        $sql = "
            SELECT
                trc.gibbonTTDayRowClassID AS classID,
                trc.gibbonCourseClassID AS courseClassID,
                trc.gibbonSpaceID AS roomID,
                cr.timeStart,
                cr.timeEnd
            FROM gibbonCourseClassPerson ccp
            JOIN gibbonTTDayRowClass trc
                ON ccp.gibbonCourseClassID = trc.gibbonCourseClassID
            JOIN gibbonTTDay td
                ON trc.gibbonTTDayID = td.gibbonTTDayID
            JOIN gibbonTTDayDate tdd
                ON td.gibbonTTDayID = tdd.gibbonTTDayID
            JOIN gibbonTTColumnRow cr
                ON trc.gibbonTTColumnRowID = cr.gibbonTTColumnRowID
            WHERE ccp.gibbonPersonID = :id
            AND tdd.date = :date
            ORDER BY cr.timeStart;
        ";

        return $this->db->select($sql, [
            'id' => $id,
            'date' => $date
        ])->fetchAll();
    }

    public function getAccessForUser($gibbonPersonID, $datetime)
    {
        // 1. Load user identity
        $user = $this->getUserById($gibbonPersonID);
        if (!$user) {
            return [
                'access' => false,
                'reason' => 'userNotFound'
            ];
        }

        $role = $user['roleName'];
        $category = $this->mapRoleToAccessCategory($role);

        // 2. Hard overrides
        if ($category === 'Admin' || $category === 'Support') {
            return [
                'access' => true,
                'reason' => 'roleOverride'
            ];
        }

        // 3. Extract date + time
        $date = date('Y-m-d', strtotime($datetime));
        $time = date('H:i:s', strtotime($datetime));

        // 4. Load timetable windows for this date
        $windows = $this->getTimetableForUser($gibbonPersonID, $date);

        if (empty($windows)) {
            return [
                'access' => false,
                'reason' => 'noTimetableToday'
            ];
        }

        // 5. Check if current time falls inside any window
        foreach ($windows as $w) {
            if ($time >= $w['timeStart'] && $time < $w['timeEnd']) {
                return [
                    'access' => true,
                    'reason' => 'timetableWindow',
                    'classID' => $w['classID'],
                    'courseClassID' => $w['courseClassID'],
                    'roomID' => $w['roomID']
                ];
            }
        }

        // 6. Otherwise deny
        return [
            'access' => false,
            'reason' => 'outsideTimetable'
        ];
    }

    private function mapRoleToAccessCategory(string $roleName): string
    {
        $roleName = strtolower(trim($roleName));

        // These names must match what getUserById() returns in roleName
        switch ($roleName) {

            case 'administrator':
            case 'admin':
            case 'principal':
            case 'head of school':
                return 'Admin';

            case 'teacher':
            case 'staff':
            case 'support staff':
            case 'counselor':
                return 'Support';

            case 'student':
                return 'Student';

            default:
                return 'Unknown';
        }
    }

}
