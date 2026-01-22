<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DistributedShop - Guía Completa</title>
  <style>
    :root {
      color-scheme: light;
      font-family: "Segoe UI", system-ui, sans-serif;
      line-height: 1.5;
      background: #f4f6fb;
      color: #1f2937;
    }

    body {
      margin: 0;
      padding: 32px 20px 60px;
    }

    main {
      max-width: 980px;
      margin: 0 auto;
      background: #ffffff;
      padding: 32px;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    h1 {
      margin-top: 0;
      font-size: 2.2rem;
    }

    h2 {
      margin-top: 32px;
      font-size: 1.45rem;
      color: #0f172a;
    }

    h3 {
      margin-top: 20px;
      font-size: 1.1rem;
      color: #1e3a8a;
    }

    p {
      margin: 12px 0;
    }

    ul {
      padding-left: 20px;
    }

    .card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 18px 20px;
      margin: 18px 0;
    }

    code {
      background: #0f172a;
      color: #e2e8f0;
      padding: 2px 6px;
      border-radius: 6px;
    }

    pre {
      background: #0b1120;
      color: #e2e8f0;
      padding: 16px;
      border-radius: 12px;
      overflow-x: auto;
      margin: 12px 0;
    }

    .label {
      display: inline-block;
      background: #dbeafe;
      color: #1d4ed8;
      padding: 2px 10px;
      border-radius: 999px;
      font-weight: 600;
      font-size: 0.85rem;
      margin-right: 8px;
    }

    .highlight {
      color: #b91c1c;
      font-weight: 700;
    }

    .grid {
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    }
  </style>
</head>
<body>
  <main>
    <h1>DistributedShop: Proyecto Completo con SQL Server Distribuido</h1>
    <p>
      Esta guía deja listo tu proyecto para que cubra todo el sílabo de Bases de Datos Distribuidas.
      Además, dentro del código te indico exactamente qué debes reemplazar para el <strong>Linked Server</strong>.
    </p>

    <section class="card">
      <h2>1) Enfoque general (Tienda vs. Bodega)</h2>
      <ul>
        <li><span class="label">Fragmentación vertical</span>Ventas locales en el nodo Tienda y stock en el nodo Bodega.</li>
        <li><span class="label">Replicación</span>Catálogo de productos copiado localmente para lecturas rápidas.</li>
        <li><span class="label">2PC</span>Transacción distribuida para asegurar consistencia (Two-Phase Commit).</li>
      </ul>
    </section>

    <section class="card">
      <h2>2) Mapeo con el sílabo</h2>
      <div class="grid">
        <div>
          <h3>Clasificación</h3>
          <p>Homogénea: ambos nodos usan SQL Server.</p>
        </div>
        <div>
          <h3>Diseño</h3>
          <p>Fragmentación (Tienda/Bodega), replicación del catálogo y consultas distribuidas.</p>
        </div>
        <div>
          <h3>Arquitectura</h3>
          <p>Cliente-servidor con sincronización punto a punto entre nodos.</p>
        </div>
        <div>
          <h3>Nube</h3>
          <p>Propuesta: migración a Azure SQL (PaaS) con firewall y credenciales seguras.</p>
        </div>
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

    <section class="card">
      <h2>8) Checklist rápido</h2>
      <ul>
        <li>Linked Server configurado y probado.</li>
        <li>MSDTC habilitado en ambos servidores.</li>
        <li>Transacción distribuida ejecutándose con COMMIT/ROLLBACK.</li>
        <li>Replicación local del catálogo funcionando.</li>
      </ul>
    </section>
  </main>
</body>
</html>
