<?php
//Activamos el almacenamiento en el buffer
ob_start();
session_start();

if (!isset($_SESSION["nombre"]))
{
  header("Location: login.html");
}
else
{
require 'header.php';
?>
<!--Contenido-->
      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">        
        <!-- Main content -->
        <section class="content">
            <div class="row">
              <div class="col-md-12">
                  <div class="box">
                    <div class="box-header with-border">
                          <h1 class="box-title">Acerca de</h1>
	                        <div class="box-tools pull-right">
	                        </div>
                    </div>
                    <!-- /.box-header -->
                    <!-- centro -->
                    <div class="panel-body">
                    <h4>Proyecto: </h4> <p>Sistema MiniMarket - Sistema de Ventas, Compras y Almacén</p>
		                <h4>Empresa: </h4> <p>Minisfot</p>
		                <h4>Desarrollado por: </h4> <p>cristiandavilavalle@gmail.com</p>
		                <h4>Web: </h4><a href="http://www.crisdava.com/p/tienda.html" target="_blank"> <p>www.crisdava.com</p></a>
		                <h4>Canal Youtube: </h4> <a href="https://www.youtube.com/@CristianDavila2002" target="_blank"><p>https://www.youtube.com/@CristianDavila2002</p></a>
                    </div>
                    <!--Fin centro -->
                  </div><!-- /.box -->
              </div><!-- /.col -->
          </div><!-- /.row -->
      </section><!-- /.content -->

    </div><!-- /.content-wrapper -->
  <!--Fin-Contenido-->
<?php
require 'footer.php';
?>
<?php 
}
ob_end_flush();
?>


