<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 18:54
 */

namespace Migrate;

use Cocur\Slugify\Slugify;
use Migrate\Utils\ArrayUtil;

class Migration
{
    private $id;
    private $description;
    private $file;
    private $appliedAt;
    private $version;
    private $sqlUp;
    private $sqlDown;
    private $allowedEnvironments = ['ANY'];

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getAppliedAt()
    {
        return $this->appliedAt;
    }

    /**
     * @param mixed $appliedAt
     */
    public function setAppliedAt($appliedAt)
    {
        $this->appliedAt = $appliedAt;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getSqlUp()
    {
        return $this->sqlUp;
    }

    /**
     * @param mixed $sqlUp
     */
    public function setSqlUp($sqlUp)
    {
        $this->sqlUp = $sqlUp;
    }

    /**
     * @return mixed
     */
    public function getSqlDown()
    {
        return $this->sqlDown;
    }

    /**
     * @param mixed $sqlDown
     */
    public function setSqlDown($sqlDown)
    {
        $this->sqlDown = $sqlDown;
    }

    /**
     * @return array
     */
    public function getAllowedEnvironments()
    {
        return $this->allowedEnvironments;
    }

    /**
     * @param array
     */
    public function setAllowedEnvironments($allowedEnvironments)
    {
        $items = array_map('strtoupper', $allowedEnvironments);
        $this->allowedEnvironments = $items;
    }

    public static function createFromFile($filename, $migrationDir)
    {
        $data = explode('_', $filename);

        $migration = new self();
        $migration->setId($data[0]);
        $migration->setAppliedAt(null);
        $migration->setVersion(null);
        $migration->setDescription(str_replace('.sql', '', str_replace('-', ' ', $data[1])));
        $migration->setFile($filename);
        $migration->load($migrationDir);

        return $migration;
    }

    public static function createFromRow(array $data, $migrationDir)
    {
        $migration = new self();
        $migration->setId(ArrayUtil::get($data, 'id'));
        $migration->setAppliedAt(ArrayUtil::get($data, 'applied_at'));
        $migration->setVersion(ArrayUtil::get($data, 'version'));
        $migration->setDescription(ArrayUtil::get($data, 'description'));

        $slugger = new Slugify();
        $filename = $migration->getId() . '_' . $slugger->slugify($migration->getDescription()) . '.sql';
        $migration->setFile($filename);

        $migration->load($migrationDir);

        return $migration;
    }

    public function toArray()
    {
        return array(
            $this->getId(),
            $this->getVersion(),
            $this->getAppliedAt(),
            $this->getDescription()
        );
    }

    public function load($migrationDir)
    {
        $content = file_get_contents($migrationDir . '/' . $this->getFile());

        if ($content) {
            if (strpos($content, '@ENVIRONMENTS') > 0) {
                if (preg_match('/-- @ENVIRONMENTS \[(.+)\]/', $content, $matches)) {
                    $tokens = explode(',', $matches[1]);
                    $this->setAllowedEnvironments(array_map('strtoupper', $tokens));
                }
            }

            if (strpos($content, '@UNDO') > 0) {
                $sql = explode('-- @UNDO', $content);
                $this->setSqlUp($sql[0]);
                $this->setSqlDown($sql[1]);
            }
        }
    }
}
