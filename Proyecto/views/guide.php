<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="/public/css/styles.css" />
</head>
<body>
  <main>
    <header class="hero">
      <div>
        <p class="eyebrow">Guía completa</p>
        <h1>DistributedShop: Proyecto Completo con SQL Server Distribuido</h1>
        <p>
          Esta guía deja listo tu proyecto para que cubra todo el sílabo de Bases de Datos Distribuidas.
          Además, aquí se indica exactamente qué debes reemplazar para el Linked Server.
        </p>
      </div>
      <div class="hero-card">
        <h2>Checklist rápido</h2>
        <ul>
          <li>Linked Server configurado y probado.</li>
          <li>MSDTC habilitado en ambos servidores.</li>
          <li>Transacción distribuida con COMMIT/ROLLBACK.</li>
          <li>Replicación local del catálogo funcionando.</li>
        </ul>
      </div>
    </header>

    <section class="card">
      <h2>1) Enfoque general (Tienda vs. Bodega)</h2>
      <ul class="badges">
        <li><span class="label">Fragmentación vertical</span>Ventas locales en el nodo Tienda y stock en el nodo Bodega.</li>
        <li><span class="label">Replicación</span>Catálogo de productos copiado localmente para lecturas rápidas.</li>
        <li><span class="label">2PC</span>Transacción distribuida para asegurar consistencia (Two-Phase Commit).</li>
      </ul>
    </section>

    <section class="card">
      <h2>2) Mapeo con el sílabo</h2>
      <div class="grid">
        <article>
          <h3>Clasificación</h3>
          <p>Homogénea: ambos nodos usan SQL Server.</p>
        </article>
        <article>
          <h3>Diseño</h3>
          <p>Fragmentación (Tienda/Bodega), replicación del catálogo y consultas distribuidas.</p>
        </article>
        <article>
          <h3>Arquitectura</h3>
          <p>Cliente-servidor con sincronización punto a punto entre nodos.</p>
        </article>
        <article>
          <h3>Nube</h3>
          <p>Propuesta: migración a Azure SQL (PaaS) con firewall y credenciales seguras.</p>
        </article>
      </div>
    </section>

    <section class="card">
      <h2>3) Configurar el Linked Server</h2>
      <p class="highlight">Reemplaza los siguientes valores en tus scripts:</p>
      <ul>
        <li><code>SERVIDOR_B</code> → Nombre del servidor remoto (Bodega).</li>
        <li><code>BodegaDB</code> → Nombre de la base de datos remota.</li>
        <li><code>UsuarioRemoto</code> / <code>PasswordRemoto</code> → Credenciales válidas.</li>
      </ul>

      <pre>
-- En el servidor anfitrión (Tienda)
EXEC sp_addlinkedserver
  @server = N'SERVIDOR_B',
  @srvproduct = N'',
  @provider = N'SQLNCLI',
  @datasrc = N'SERVIDOR_B';

EXEC sp_addlinkedsrvlogin
  @rmtsrvname = N'SERVIDOR_B',
  @useself = 'false',
  @rmtuser = N'UsuarioRemoto',
  @rmtpassword = N'PasswordRemoto';
      </pre>
    </section>

    <section class="card">
      <h2>4) Transacción distribuida (2PC)</h2>
      <p>Este procedimiento demuestra control de concurrencia y recuperación. Debes tener MSDTC activo en ambos nodos.</p>
      <pre>
CREATE PROCEDURE RegistrarVentaDistribuida
  @IdProducto INT,
  @Cantidad INT
AS
BEGIN
  BEGIN DISTRIBUTED TRANSACTION;

  BEGIN TRY
    -- Operación local (Tienda)
    INSERT INTO Ventas (Fecha, Total)
    VALUES (GETDATE(), 0);

    -- Operación remota (Bodega)
    UPDATE [SERVIDOR_B].[BodegaDB].[dbo].[Productos]
    SET Stock = Stock - @Cantidad
    WHERE Id = @IdProducto;

    COMMIT TRANSACTION;
  END TRY
  BEGIN CATCH
    ROLLBACK TRANSACTION;
    PRINT 'Error: no se pudo completar la venta (fallo de red o deadlock).';
  END CATCH
END
      </pre>
    </section>

    <section class="card">
      <h2>5) Replicación simple del catálogo</h2>
      <p>Usa un botón "Sincronizar Catálogo" en la app para ejecutar este script.</p>
      <pre>
TRUNCATE TABLE Productos_Replica_Local;

INSERT INTO Productos_Replica_Local
SELECT *
FROM [SERVIDOR_B].[BodegaDB].[dbo].[Productos];
      </pre>
    </section>

    <section class="card">
      <h2>6) Consultas centralizadas vs. distribuidas</h2>
      <pre>
-- Consulta local
SELECT * FROM Ventas;

-- Consulta distribuida
SELECT * FROM [SERVIDOR_B].[BodegaDB].[dbo].[Clientes];
      </pre>
    </section>

    <section class="card">
      <h2>7) Estructura recomendada del informe técnico</h2>
      <ol>
        <li>Introducción y objetivos.</li>
        <li>Arquitectura del sistema (diagrama cliente-servidor/punto a punto).</li>
        <li>Clasificación (BD distribuida homogénea).</li>
        <li>Diseño de la distribución (fragmentación Tienda/Bodega).</li>
        <li>Replicación y análisis de ventajas/desventajas.</li>
        <li>Control de concurrencia (evidencia de 2PC).</li>
        <li>Análisis de nube: propuesta Azure SQL (PaaS).</li>
        <li>Conclusiones.</li>
      </ol>
    </section>
  </main>
</body>
</html>
