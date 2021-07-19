<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Mailer;
use \Hcode\Model;
use \Hcode\Model\Product;

class Category extends Model
{

    public static function listAll()
    {
        $sql = new SqL();
        $results = $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
        return $results;
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
            ":idcategory" => $this->getidcategory(),
            ":descategory" => $this->getdescategory(),
        ));
        $this->setData($results[0]);


        Category::updateFile();
    }

    public function get($id)
    {

        $sql = new Sql();
        $results = $sql->select(
            "
        SELECT * 
        FROM tb_categories
        WHERE idcategory = :id",
            ["id" => $id]
        );

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();
        $sql->query(
            "
        DELETE FROM tb_categories
        WHERE idcategory = :id",
            [":id" => $this->getidcategory()]
        );

        Category::updateFile();
    }

    public static function updateFile()
    {
        $categories = Category::listAll();
        $html = [];

        foreach ($categories as $value) {
            array_push($html, '<li><a href="/categories/' . $value['idcategory'] . '">' . $value['descategory'] . '</a></li>');
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
        }
    }

    public function getProducts($related = true)
    {

        $sql = new Sql();

        if ($related == true) {
            return  $sql->select(
                "
            SELECT * FROM tb_products WHERE idproduct IN(
                SELECT a.idproduct
                FROM tb_products a
                INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                WHERE b.idcategory = :id);
            ",
                [":id" => $this->getidcategory()]
            );
        } else {
            return $sql->select(
                "
            SELECT * FROM tb_products WHERE idproduct NOT IN(
                SELECT a.idproduct
                FROM tb_products a
                INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                WHERE b.idcategory = :id);
            ",
                [":id" => $this->getidcategory()]
            );
        }
    }

	public function getProductsPage($page = 1, $itemsPerPage = 3)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a
			INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
			INNER JOIN tb_categories c ON c.idcategory = b.idcategory
			WHERE c.idcategory = :idcategory
			LIMIT $start, $itemsPerPage;
		", [
			':idcategory'=>$this->getidcategory()
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>Product::checkLista($results),
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
    }
    public function addProduct(Product $product)
    {

        $sql = new Sql();
        $sql->query(
            "INSERT INTO tb_productscategories (idcategory, idproduct)
                    VALUES (:idcategory, :idproduct)",
            [
                ":idcategory" => $this->getidcategory(),
                ":idproduct" => $product->getidproduct()
            ]
        );
    }

    public function removeProduct(Product $product)
    {

        $sql = new Sql();
        $sql->query(
            "DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND
            idproduct = :idproduct)",
            [
                ":idcategory" => $this->getidcategory(),
                ":idproduct" => $product->getidproduct()
            ]
        );
    }
}
