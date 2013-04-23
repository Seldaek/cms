<?php
namespace Craft;

/**
 * Asset element type
 */
class AssetElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Assets');
	}

	/**
	 * Returns whether this element type can have thumbnails.
	 *
	 * @return bool
	 */
	public function hasThumbs()
	{
		return true;
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @return array|false
	 */
	public function getSources()
	{
		$sources = array();

		foreach (craft()->assetSources->getViewableSources() as $source)
		{
			$key = 'source:'.$source->id;

			$sources[$key] = array(
				'label'    => $source->name,
				'criteria' => array('sourceId' => $source->id)
			);
		}

		return $sources;
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'filename'     => Craft::t('Filename'),
			'dateModified' => Craft::t('Date Modified'),
			'size'         => Craft::t('Size'),
			'kind'         => Craft::t('Kind'),
		);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'sourceId' => AttributeType::Number,
			'folderId' => AttributeType::Number,
			'filename' => AttributeType::String,
			'kind'     => AttributeType::String,
			'order'    => array(AttributeType::String, 'default' => 'filename asc'),
		);
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('assetfiles.sourceId, assetfiles.folderId, assetfiles.filename, assetfiles.kind, assetfiles.width, assetfiles.height, assetfiles.size, assetfiles.dateModified')
			->join('assetfiles assetfiles', 'assetfiles.id = elements.id');

		if ($criteria->sourceId)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.sourceId', $criteria->sourceId, $query->params));
		}

		if ($criteria->folderId)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.folderId', $criteria->folderId, $query->params));
		}

		if ($criteria->filename)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.filename', $criteria->filename, $query->params));
		}

		if ($criteria->kind)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.kind', $criteria->kind, $query->params));
		}
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return AssetFileModel::populateModel($row);
	}
}
