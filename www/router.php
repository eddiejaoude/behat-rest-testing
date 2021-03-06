<?php
/**
 * @author   Demin Yin <deminy@deminy.net>
 * @license  MIT license
 */

/**
 * This script simulates 4 types REST services (GET, POST and PUT, DELETE), manipuating employee data which are stored
 * in file "employees.txt" in following format as a serialized string:
 *     array(
 *         // indexes are employee IDs
 *         3 => array(
 *             'name' => 'tom',
 *             'age'  => 15,
 *         ),
 *         // ......
 *     );
 * );
 */

/**
 * Handle bad HTTP request.
 *
 * @param   string  $message  Message to be returned for a bad HTTP request.
 *
 * @return  void
 */
function badRequest($message)
{
    header('HTTP/1.1 400 Bad Request');
    exit($message);
}

$file = __DIR__ . '/employees.txt';

// Get all employees information.
$data = is_readable($file) ? file_get_contents($file) : null;
$employees = !empty($data) ? unserialize($data) : array();

// Validate request URL.
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (in_array($_SERVER['REQUEST_URI'], array('', '/'))) {
            exit('OK');
        }
        // NOTE: no break statement here.
    case 'DELETE':
        if (!preg_match('#^/employee/(\d+)$#', $_SERVER['REQUEST_URI'], $matches)) {
            badRequest('Bad REST request.');
        } else {
            $employeeId = (int) $matches[1];
        }
        break;
    case 'POST':
    case 'PUT':
        if ('/employee' != $_SERVER['REQUEST_URI']) {
            badRequest('Bad REST request.');
        } else {
            /**
             * For PUT requests, variable $_REQUEST might always be empty when using PHP 5.4+ built-in web server.
             */
            $rawRequestData = file_get_contents('php://input');

            if (!empty($rawRequestData)) {
                parse_str($rawRequestData, $requestData);
                $employeeId = (int) $requestData['employeeId'];
            } else {
                badRequest('Unsupported REST request.');
            }
        }
        break;
    default:
        badRequest('Unsupported REST request.');
        break;
}

// Process request.
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        exit(array_key_exists($employeeId, $employees) ? json_encode($employees[$employeeId]) : json_encode(false));
        break;
    case 'POST':
        if (!array_key_exists($employeeId, $employees)) {
            $employees[$employeeId] = array(
                'name' => array_key_exists('name', $requestData) ? $requestData['name'] : null,
                'age'  => array_key_exists('age', $requestData) ? (int) $requestData['age'] : null,
            );
            file_put_contents($file, serialize($employees));
        } else {
            badRequest('Unable to insert because the employee already exists.');
        }
        break;
    case 'PUT':
        if (array_key_exists($employeeId, $employees)) {
            if (array_key_exists('name', $requestData)) {
                $name = $requestData['name'];
            } else {
                $name = array_key_exists('name', $employees[$employeeId]) ? $employees[$employeeId]['name'] : null;
            }

            if (array_key_exists('age', $requestData)) {
                $age = (int) $requestData['age'];
            } else {
                $age = array_key_exists('age', $employees[$employeeId]) ? $employees[$employeeId]['age'] : null;
            }

            $employees[$employeeId] = array(
                'name' => $name,
                'age'  => $age,
            );
            file_put_contents($file, serialize($employees));
        } else {
            badRequest('Unable to update because the employee does not exist.');
        }
        break;
    case 'DELETE':
        if (array_key_exists($employeeId, $employees)) {
            unset($employees[$employeeId]);
            file_put_contents($file, serialize($employees));
        } else {
            badRequest('Unable to delete because the employee does not exist.');
        }
        break;
    default:
        badRequest('Unsupported REST request.');
        break;
}

exit(json_encode(true));
