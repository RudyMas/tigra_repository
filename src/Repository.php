<?php

namespace Tiger;

use Exception;
use PDO;
use RudyMas\DBconnect;

/**
 * Class Repository (PHP version 7.4)
 *
 * @author      Rudy Mas <rudy.mas@rmsoft.be>
 * @copyright   2022, rmsoft.be. (https://www.rmsoft.be/)
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version     7.4.0.5
 * @package     Tiger
 */
class Repository
{
    private array $data = [];
    private int $indexMarker = 0;
    protected DBconnect $DB;

    /**
     * Repository constructor.
     * @param null|DBconnect $db
     * @param null $object
     */
    public function __construct(?DBconnect $db, $object = null)
    {
        if ($object !== null) {
            $this->data[] = $object;
        }
        if ($db !== null) {
            $this->DB = $db;
        }
    }

    /**
     * @param $object
     */
    public function add($object): void
    {
        $this->data[] = $object;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * @param int $id
     * @return mixed
     * @throws Exception
     */
    public function getByIndex(int $id)
    {
        foreach ($this->data as $value) {
            if ($value->getData('id') == $id) {
                return $value;
            }
        }
        throw new Exception('<b>ERROR:</b> Call to an unknown repository Index!');
    }

    /**
     * @param string $field
     * @param string $search
     * @return array
     */
    public function getBy(string $field, string $search): array
    {
        $output = [];
        foreach ($this->data as $value) {
            if ($value->getData($field) == $search) {
                $output[] = $value;
            }
        }

        return $output;
    }

    /**
     * @return bool
     */
    public function hasNext(): bool
    {
        if (isset($this->data[$this->indexMarker + 1])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function hasPrevious(): bool
    {
        if (isset($this->data[$this->indexMarker - 1])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool|mixed
     */
    public function current()
    {
        return (isset($this->data[$this->indexMarker])) ? $this->data[$this->indexMarker] : false;
    }

    /**
     * @return false|mixed
     */
    public function first()
    {
        return (isset($this->data[0])) ? $this->data[0] : false;
    }

    /**
     * @return false|mixed
     */
    public function last()
    {
        return (isset($this->data[count($this->data) - 1])) ? $this->data[count($this->data) - 1] : false;
    }

    /**
     * Update current object with new data
     *
     * @param $object
     *
     * @return void
     */
    public function updateCurrent($object)
    {
        $this->data[$this->indexMarker] = $object;
    }

    /**
     * @return mixed
     */
    public function next()
    {
        $this->indexMarker++;
        return $this->current();
    }

    /**
     * @return mixed
     */
    public function previous()
    {
        $this->indexMarker--;
        return $this->current();
    }

    public function reset()
    {
        $this->indexMarker = 0;
    }

    /**
     * Clearing Data
     */
    public function clearData(): void
    {
        $this->data = [];
    }

    /**
     * @param string $model
     * @param string $table
     */
    public function loadAllFromTable(string $model, string $table): void
    {
        $newModel = '\\Models\\' . $model;
        $query = "SELECT * FROM {$table}";
        $this->DB->query($query);
        $this->DB->fetchAll();
        foreach ($this->DB->data as $data) {
            $this->data[] = $newModel::new($data);
        }
    }

    /**
     * @param string $model
     * @param string $preparedStatement
     * @param array $keyBindings
     */
    public function loadAllFromTableByQuery(string $model, string $preparedStatement, array $keyBindings = []): void
    {
        $newModel = '\\Models\\' . $model;
        $statement = $this->DB->prepare($preparedStatement);
        foreach ($keyBindings as $key => $value) {
            $statement->bindValue($key, $value, $this->PDOParameter($value));
        }
        $statement->execute();
        $this->DB->rows = $statement->rowCount();
        $tableData = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tableData as $data) {
            $this->data[] = $newModel::new($data);
        }
    }

    /**
     * @param  string  $model
     * @param  string  $table
     */
    public function loadAllFromTableWithFilter(string $model, string $table): void
    {
        $newModel = '\\Models\\' . $model;
        $query = "SELECT * FROM {$table}";

        if (!empty($_GET)) {
            $where = '';
            foreach ($_GET as $key => $value) {
                if (empty($where)) {
                    $where .= ' WHERE ' . $key . ' IN (' . $value . ')';
                } else {
                    $where .= ' AND ' . $key . ' IN (' . $value . ')';
                }
            }
            $query .= $where;
        }

        $this->DB->query($query);
        $this->DB->fetchAll();
        foreach ($this->DB->data as $data) {
            $this->data[] = $newModel::new($data);
        }
    }

    /**
     * @param string $preparedStatement
     * @param array $keyBindings
     * @return bool
     */
    public function executeQuery(string $preparedStatement, array $keyBindings = []): bool
    {
        $statement = $this->DB->prepare($preparedStatement);
        foreach ($keyBindings as $key => $value) {
            $statement->bindValue($key, $value, $this->PDOParameter($value));
        }
        $status = $statement->execute();
        $this->DB->rows = $statement->rowCount();
        return $status;
    }

    /**
     * @return int
     */
    public function getRows(): int
    {
        return $this->DB->rows;
    }

    /**
     * @param string $sql
     * @param array $keyBindings
     * @return int
     */
    public function getRowsByQuery(string $sql, array $keyBindings): int
    {
        $this->executeQuery($sql, $keyBindings);
        return $this->getRows();
    }

    /**
     * @return int
     */
    public function getLastInsertedId(): int
    {
        return $this->DB->lastInsertId();
    }

    /**
     * @param $value
     * @return int
     */
    public function PDOParameter($value): int
    {
        if (is_integer($value)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } elseif (is_null($value)) {
            return PDO::PARAM_NULL;
        } elseif (is_string($value)) {
            return PDO::PARAM_STR;
        }
    }

    /**
     * Create the set with the provided data
     *
     * @param  array  $data  Array of the data
     *
     * @return string
     */
    public function createSet(array $data): string
    {
        $set = '';
        foreach ($data as $key => $item) {
            if (!empty($item)) {
                if (empty($set)) {
                    $set = "{$key} = '{$item}'";
                } else {
                    $set .= ", {$key} = '{$item}'";
                }
            }
        }
        return $set;
    }

    /**
     * Create the values with the provided data
     *
     * @param  array  $data
     *
     * @return string
     */
    public function createValues(array $data): string
    {
        unset($data['id']);
        $values = '(0';
        foreach ($data as $item) {
            $item = $this->prepareSQLData($item) ? 'null' : "'{$item}'";
            $values .= ", {$item}";
        }
        $values .= ')';

        return $values;
    }

    /**
     * Preparing the data for SQL values
     *
     * @param $var
     *
     * @return bool
     */
    public function prepareSQLData($var): bool
    {
        if (empty($var) === true) {
            if (($var === 0) || ($var === '0')) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Checking the array
     * if empty return string '0' else return string from array
     *
     * @param  array  $ids
     *
     * @return string
     */
    public function checkIds(array $ids): string
    {
        if (empty($ids)) {
            $ids = '0';
        } else {
            $ids = implode(',', $ids);
        }
        return $ids;
    }
}
