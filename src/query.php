<?
public static function getAdditionalMap($iblockId = null)
{
    $map = array();

    $iblockId = $iblockId === null ? static::getIblockId() : $iblockId;

    if (!$iblockId) return $map;

    $iblock = IblockStructure::iblock($iblockId);
    $props  = IblockStructure::properties($iblockId);

    if (empty($props) || empty($iblock)) return $map;

    $isOldProps = $iblock['VERSION'] == 1;

    $singlePropTableLinked = false;
    $singlePropsEntityName = "PROPERTY_TABLE_IBLOCK_{$iblock['ID']}";

    if (!$isOldProps)
    {
        $singleProp   = ElementPropSingleTable::getInstance($iblock['ID'])->getEntity()->getDataClass();
        $multipleProp = ElementPropMultipleTable::getInstance($iblock['ID'])->getEntity()->getDataClass();
    }
    else
    {
        $singleProp = ElementPropertyTable::getEntity()->getDataClass();
    }

    foreach ($props as $propCode => $prop)
    {
        if (is_numeric($propCode)) continue;

        $propId         = $prop['ID'];
        $isMultiple     = $prop['MULTIPLE'] == 'Y';
        $useDescription = $prop['WITH_DESCRIPTION'] == 'Y';
        $isNewMultiple  = $isMultiple && !$isOldProps;

        $propTableEntityName        = "PROPERTY_{$propCode}";
        $propValueShortcut          = "PROPERTY_{$propCode}_VALUE";
        $propValueDescriptionShortcut = "PROPERTY_{$propCode}_DESCRIPTION";

        $sep            = static::$concatSeparator;
        $concatSubquery = "GROUP_CONCAT(%s SEPARATOR '{$sep}')";

        $propValueColumn = $isMultiple || $isOldProps ? 'VALUE' : "PROPERTY_{$propId}";

        $valueReference = $valueEntity = $fieldReference = null;

        // ---------------------------------------------------------------
        // МНОЖЕСТВЕННЫЕ СВОЙСТВА
        // Вместо JOIN используем коррелированный подзапрос, чтобы избежать
        // декартова произведения когда у элемента несколько множественных свойств.
        // ---------------------------------------------------------------
        if ($isMultiple)
        {
            $propTableName = !$isOldProps
                ? $multipleProp::getTableName()
                : ElementPropertyTable::getTableName();

            $valueEntity = new ExpressionField(
                $propValueShortcut,
                "(SELECT GROUP_CONCAT(`VALUE` ORDER BY `ID` SEPARATOR '{$sep}')
                  FROM `{$propTableName}`
                  WHERE `IBLOCK_ELEMENT_ID` = %s
                  AND `IBLOCK_PROPERTY_ID` = {$propId})",
                ['ID']
            );
            $valueEntity->addFetchDataModifier([__CLASS__, 'multiValuesDataModifier']);
            $map[$propValueShortcut] = $valueEntity;

            if ($useDescription)
            {
                $descEntity = new ExpressionField(
                    $propValueDescriptionShortcut,
                    "(SELECT GROUP_CONCAT(`DESCRIPTION` ORDER BY `ID` SEPARATOR '{$sep}')
                      FROM `{$propTableName}`
                      WHERE `IBLOCK_ELEMENT_ID` = %s
                      AND `IBLOCK_PROPERTY_ID` = {$propId})",
                    ['ID']
                );
                $descEntity->addFetchDataModifier([__CLASS__, 'multiValuesDataModifier']);
                $map[$propValueDescriptionShortcut] = $descEntity;
            }

            // Reference оставляем — он нужен если по этому полю делают addFilter().
            // В SELECT он участвовать не будет, так как значение берётся из подзапроса.
            $map[$propTableEntityName] = new Reference(
                $propTableEntityName,
                $isNewMultiple ? $multipleProp : $singleProp,
                [
                    '=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                    '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $propId)
                ],
                ['join_type' => 'LEFT']
            );

            continue;
        }

        // ---------------------------------------------------------------
        // ОДИНОЧНЫЕ СВОЙСТВА
        // ---------------------------------------------------------------

        /**
         * Для не множественных свойств 2.0 цепляем только одну сущность
         */
        if (!$isOldProps)
        {
            if (!$singlePropTableLinked)
            {
                $map[$singlePropsEntityName] = new Reference(
                    $singlePropsEntityName,
                    $singleProp,
                    array('=ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
                    array('join_type' => 'LEFT')
                );
                $singlePropTableLinked = true;
            }
        }

        $realValueStorage = !$isOldProps
            ? "{$singlePropsEntityName}.{$propValueColumn}"
            : "{$propTableEntityName}.{$propValueColumn}";

        switch ($prop['PROPERTY_TYPE'])
        {
            case 'E':
                $valueReference = array("=this.{$realValueStorage}" => 'ref.ID');
                $entityName = '\\' . __CLASS__;
                if ($prop['LINK_IBLOCK_ID'])
                {
                    $entityName = $iblockId == $prop['LINK_IBLOCK_ID']
                        ? get_called_class()
                        : self::compileEntity($prop['LINK_IBLOCK_ID'])->getDataClass();
                    $valueReference['=ref.IBLOCK_ID'] = new SqlExpression('?i', $prop['LINK_IBLOCK_ID']);
                }
                $valueEntity = new Reference(
                    $propValueShortcut,
                    $entityName,
                    $valueReference
                );
                break;

            case 'G':
                $valueReference = array("=this.{$realValueStorage}" => 'ref.ID');
                $entityName = __NAMESPACE__ . '\\SectionTable';
                if ($prop['LINK_IBLOCK_ID'])
                {
                    $entityName = $entityName::compileEntity($prop['LINK_IBLOCK_ID'])->getDataClass();
                    $valueReference['=ref.IBLOCK_ID'] = new SqlExpression('?i', $prop['LINK_IBLOCK_ID']);
                }
                $valueEntity = new Reference(
                    $propValueShortcut,
                    $entityName,
                    $valueReference
                );
                break;

            case 'L':
                $valueReference = array(
                    '=ref.PROPERTY_ID' => new SqlExpression('?i', $prop['ID']),
                    "=this.{$realValueStorage}" => 'ref.ID'
                );
                $valueEntity = new Reference(
                    $propValueShortcut,
                    '\Bitrix\Iblock\PropertyEnumerationTable',
                    $valueReference
                );
                break;

            case 'S':
            case 'N':
                $valueEntity = new ExpressionField(
                    $propValueShortcut,
                    '%s',
                    $realValueStorage
                );
                break;

            case 'F':
                $valueReference = array(
                    "=this.{$realValueStorage}" => 'ref.ID'
                );
                $valueEntity = new Reference(
                    $propValueShortcut,
                    '\Bitrix\Main\FileTable',
                    $valueReference
                );
                break;

            default:
                break;
        }

        if (!$valueEntity) {
            continue;
        }

        if ($isOldProps)
        {
            $map[$propTableEntityName] = new Reference(
                $propTableEntityName,
                $singleProp,
                array(
                    '=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                    '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $propId)
                ),
                array('join_type' => 'LEFT')
            );

            $map[$propValueShortcut] = $valueEntity;

            if ($useDescription)
            {
                $e = new ExpressionField(
                    $propValueDescriptionShortcut,
                    '%s',
                    "{$propTableEntityName}.DESCRIPTION"
                );
                $map[$propValueDescriptionShortcut] = $e;
            }
        }
        else
        {
            $map[$propTableEntityName] = new Reference(
                $propTableEntityName,
                $singleProp,
                array('=ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
                array('join_type' => 'LEFT')
            );

            $map[$propValueShortcut] = $valueEntity;

            if ($useDescription)
            {
                $map[$propValueDescriptionShortcut] = new ExpressionField(
                    $propValueDescriptionShortcut,
                    '%s',
                    "{$singlePropsEntityName}.DESCRIPTION_{$propId}"
                );
            }
        }
    }

    /**
     * Добавим DETAIL_PAGE_URL
     */
    $e = new ExpressionField('DETAIL_PAGE_URL', '%s', 'IBLOCK.DETAIL_PAGE_URL');
    $e->addFetchDataModifier(function($value, $query, $entry, $fieldName)
    {
        $search  = array();
        $replace = array();
        foreach ($entry as $key => $val)
        {
            $search[]  = "#{$key}#";
            $replace[] = $val;
        }
        return str_replace($search, $replace, $value);
    });
    $map['DETAIL_PAGE_URL'] = $e;

    return $map;
}