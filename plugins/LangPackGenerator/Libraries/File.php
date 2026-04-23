<?php

namespace Plugin\LangPackGenerator\Libraries;

class File
{

    /**
     * 获取目录下所有文件
     * @param $dir
     * @return array
     */
    static function get_php_files($dir)
    {
        $files    = glob($dir . '/*.php');           // 获取当前目录下所有 PHP 文件
        $sub_dirs = glob($dir . '/*', GLOB_ONLYDIR); // 获取当前目录下所有子目录

        // 递归获取子目录中的 PHP 文件
        foreach ($sub_dirs as $sub_dir) {
            $sub_files = self::get_php_files($sub_dir);
            $files     = array_merge($files, $sub_files);
        }

        // 将相对路径转换为绝对路径
        $files = array_map('realpath', $files);

        return $files;
    }

    /**
     * 文件夹文件拷贝
     *
     * @param string $src 来源文件夹
     * @param string $dst 目的地文件夹
     * @return bool
     */
    static function mvdir($src = '', $dst = '')
    {
        if (empty($src) || empty($dst)) {
            return false;
        }

        $dir = opendir($src);
        self::dir_mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::mvdir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);

        return true;
    }


    /**
     * 创建文件夹
     *
     * @param string $path 文件夹路径
     * @param int $mode 访问权限
     * @param bool $recursive 是否递归创建
     * @return bool
     */
    static function dir_mkdir($path = '', $mode = 0777, $recursive = true)
    {
        clearstatcache();
        if (!is_dir($path)) {
            mkdir($path, $mode, $recursive);
            return chmod($path, $mode);
        }

        return true;
    }

    /**
     * 获取目录下所有类文件信息
     * @param $directory
     * @return array
     */
    public static function dir_class_files($directory)
    {
        $class = [];
        // 获取目录下的所有文件
        $files = scandir($directory);

        // 遍历文件
        foreach ($files as $file) {
            // 排除当前目录和上级目录
            if ($file === '.' || $file === '..') {
                continue;
            }

            // 文件路径
            $filePath = $directory . '/' . $file;

            // 判断是否为 PHP 文件
            if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                // 读取文件内容
                $fileContent = file_get_contents($filePath);

                // 使用正则表达式匹配命名空间和类名
                $pattern = '/namespace\s+(.*?);.*?class\s+(.*?)\s+/s';
                preg_match($pattern, $fileContent, $matches);

                // 判断是否匹配成功
                if (isset($matches[1]) && isset($matches[2])) {
                    $namespace = $matches[1];
                    $className = $matches[2];
                    $class[]     = [
                        'namespace' => $namespace,
                        'class'     => $className
                    ];
                    // 输出命名空间和类名

                }
            }
        }
        return $class;

    }


}
