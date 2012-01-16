<?PHP

### website's path
function site_path($path)
{
    $last = $path[strlen($path)-1];
    if ($last == DIRECTORY_SEPARATOR)
    {
	return $path;
    }
    else
    {
	return $path . DIRECTORY_SEPARATOR;
    }
}
define("WEBSITE_PATH", site_path($_SERVER['DOCUMENT_ROOT']));	# with / at the end

?>
