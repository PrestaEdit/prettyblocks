<?php 
/**
 * Please don't judge me
 * it will be refactoring soon. 
 */
use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;

class FieldFormatter 
{

    public static $suffix = '_config';

    public static function setSuffix($key)
    {
        self::$suffix = $key;
    }
    public static function formatFieldText($name,$data,$block = false,$context = 'front')
    {
        $default_value = ($data['default']) ?? '';
        $key = self::getKey($name,$block);
        $res = Configuration::get($key);
        if(!$res)
        {
            $res = $default_value;
        }
        return $res;
    }


    public static function formatFieldBoxes($name,$data,$block = false,$context = 'front')
    {
        $default_value = ($data['default']) ? filter_var($data['default'], FILTER_VALIDATE_BOOLEAN) : false;
        $key = self::getKey($name,$block);
        if(!Configuration::hasKey($key))
        {   
            return $default_value;
        }
        $res = filter_var(Configuration::get($key), FILTER_VALIDATE_BOOLEAN);
        return $res;
    }

    public static function formatFieldUpload($name,$data,$block = false,$context = 'front')
    {
        $default_value = ($data['default']) ?? '';
        $key = self::getKey($name,$block);
        $res = Configuration::get($key);
        if(!$res)
        {
            $res = $default_value;
        } else {
            $res = json_decode($res, true);
        }

        return $res;
    }
    /**
     * Format a field type selector
     * @return ObjectPresenter
     */
    public static function formatFieldSelector($name,$data,$block = false,$context = 'front')
    {
        $collection = false;
        $key = self::getKey($name,$block);
        $res = Configuration::get($key);
        if(!$res)
        {
            return $collection;
        }


        if($data['collection'])
        {
            if(!Validate::isJson($res))
            {
                return false;
            }
            
            if($context == 'back')
            {
                return json_decode($res, true);
            }
            $json = json_decode($res, true);
            if(!isset($json['show']['id']))
            {
                return false;
            }
            $c = new PrestaShopCollection($data['collection'], Context::getContext()->language->id);
            $primary = ($data['primary']) ?? 'id_'.Tools::strtolower($data['collection']);
            $object =  $c->where($primary, '=' , (int)$json['show']['id'])->getFirst();
            $objectPresenter = new ObjectPresenter();

            return $objectPresenter->present($object);
        }

        return $collection;
    }
/**
     * Format a field type selector
     * @return ObjectPresenter
     */
    public static function formatFieldSelect($name,$data,$block = false,$context = 'front')
    {

        $key = self::getKey($name,$block);
        $res = Configuration::get($key);
        if($res === false)
        {
            if(!isset($data['choices']))
            {
                throw new Exception('Option: "choices" must be present in the field nammed: "'.$name.'"');
            }
            if(isset($data['default']))
            {
                if($context == 'back')
                {
                    return $data['default'];
                }
                return $data['choices'][$data['default']];
            }
            if($context == 'back')
            {
                return array_key_first($data['choices']);
            }
            return $data['choices'][array_key_first($data['choices'])];
        }
        if($context == 'back')
        {
            return $res;
        }
        return $data['choices'][$res];

    }
    public static function formatFieldRadioGroup($name,$data,$block = false,$context = 'front')
    {
        return self::formatFieldSelect($name,$data,$block,$context);
    }
    

    private static function getKey($name, $block = false)
    {
        if(!$block)
        {
            $key = Tools::strtoupper($name.self::$suffix);
        }else{
            $key = Tools::strtoupper($block['id_prettyblocks'].'_'.$name.self::$suffix);
        }
        return $key;
    }

    public static function matchColumnsWithCollection($collection,$columns, $prefix = 'a1.')
    {
        $def = ObjectModel::getDefinition($collection);
        // dump($def['primary']);
        // die();
        $search_columns = [];
        foreach($columns as $column)
        {
            // dump($column);
            // dump(isset($def['fields'][$column]));
            if(isset($def['fields'][$column]))
            {
                $name = $column;
                if(isset($def['fields'][$column]['lang']) && $def['fields'][$column]['lang'])
                {
                    $name = $prefix.$column;
                }
                $search_columns[] = $name;
            }
        }
        if(!in_array($def['primary'],$search_columns))
        {
            $search_columns['primary'] = $prefix.$def['primary'];
        }
        return $search_columns;
    }
    public static function formatSelectorsToArray($selectors)
    {
        $regex = "/\{.*?\}/";
        $columns = [];
        preg_match_all($regex, $selectors,$terms);
        foreach($terms[0] as $term)
        {
            $column = str_replace('{','',$term);
            $column = str_replace('}','',$column);
            $columns[] = $column;
        }
        return $columns;
    }
}