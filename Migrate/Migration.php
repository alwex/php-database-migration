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

    public static function createFromFile($filename)
    {
        $data = explode('_', $filename);

        $migration = new self();
        $migration->setId($data[0]);
        $migration->setAppliedAt(null);
        $migration->setVersion(null);
        $migration->setDescription(str_replace('.sql', '', str_replace('-', ' ', $data[1])));

        return $migration;
    }

    public static function createFromRow(array $data)
    {
        $migration = new self();
        $migration->setId(ArrayUtil::get($data, 'id'));
        $migration->setAppliedAt(ArrayUtil::get($data, 'applied_at'));
        $migration->setVersion(ArrayUtil::get($data, 'version'));
        $migration->setDescription(ArrayUtil::get($data, 'description'));

        return $migration;
    }

    public function toArray() {
        return array(
            $this->getId(),
            $this->getVersion(),
            $this->getAppliedAt(),
            $this->getDescription()
        );
    }
}