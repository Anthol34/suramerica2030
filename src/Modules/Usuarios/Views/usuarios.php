<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="text-primary">Gestión de Usuarios, Roles y Sucursales</h3>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
        <?php 
            if($_GET['status'] == 'success') echo '✅ Usuario registrado exitosamente.';
            elseif($_GET['status'] == 'success_edit') echo '✅ Información actualizada.';
            elseif($_GET['status'] == 'success_status') echo '✅ Estado modificado.';
            elseif($_GET['status'] == 'success_rol') echo '✅ Nuevo rol creado exitosamente.';
            elseif($_GET['status'] == 'success_permisos') echo '🔐 Permisos del rol actualizados correctamente. (Los usuarios de este rol deberán reiniciar sesión).';
            elseif($_GET['status'] == 'success_sucursal') echo '🏢 Nueva sucursal registrada con éxito.';
            elseif($_GET['status'] == 'success_edit_sucursal') echo '🏢 Información de la sucursal actualizada.';
            elseif($_GET['status'] == 'success_delete_sucursal') echo '🗑️ Sucursal eliminada correctamente.';
            elseif($_GET['status'] == 'success_email_cfg') echo '📧 Servidor SMTP actualizado. Los PDF ahora se enviarán desde esta cuenta.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- NAVEGACIÓN DE PESTAÑAS -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= (!isset($_GET['tab']) || $_GET['tab'] == 'usuarios') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#usuarios" type="button">👥 Personal Activo</button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= (isset($_GET['tab']) && $_GET['tab'] == 'roles') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#roles" type="button">🔐 Roles y Privilegios</button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= (isset($_GET['tab']) && $_GET['tab'] == 'sucursales') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#sucursales" type="button">📍 Sucursales</button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= (isset($_GET['tab']) && $_GET['tab'] == 'email') ? 'active fw-bold text-primary' : '' ?>" data-bs-toggle="tab" data-bs-target="#email" type="button">📧 Servidor de Correos</button>
    </li>
</ul>

<div class="tab-content">
    
    <!-- ==================== PESTAÑA USUARIOS ==================== -->
    <div class="tab-pane fade <?= (!isset($_GET['tab']) || $_GET['tab'] == 'usuarios') ? 'show active' : '' ?>" id="usuarios">
        <div class="text-end mb-3">
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">+ Registrar Nuevo Usuario</button>
        </div>

        <div class="card shadow-sm border-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Personal</th><th>Acceso (Login)</th><th>Rol / Sucursal</th><th>Estado</th><th class="text-center">Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $u): ?>
                        <tr id="fila_usuario_<?= $u['ID_USUARIO'] ?>" class="<?= $u['ESTADO'] == 0 ? 'table-secondary opacity-75' : '' ?>">
                            <td><span class="badge bg-secondary">#<?= $u['ID_USUARIO'] ?></span></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($u['NOMBRE']) ?></strong><br>
                                <small class="text-muted">✉️ <?= htmlspecialchars($u['EMAIL']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-primary border fw-bold fs-6">👤 <?= htmlspecialchars($u['USUARIO']) ?></span></td>
                            <td>
                                <span class="badge bg-info text-dark fw-bold"><?= htmlspecialchars($u['NOMBRE_ROL']) ?></span><br>
                                <small class="text-muted">📍 <?= htmlspecialchars($u['CIUDAD']) ?></small>
                            </td>
                            <td><?= $u['ESTADO'] == 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarUsuario<?= $u['ID_USUARIO'] ?>">✏️ Editar</button>
                                    <form action="/suramerica2030/usuarios/estado" method="POST" style="display:inline;" data-live="true" data-live-refresh="#usuarios" data-live-row="#fila_usuario_<?= $u['ID_USUARIO'] ?>">
                                        <input type="hidden" name="id_usuario" value="<?= $u['ID_USUARIO'] ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?= $u['ESTADO'] == 1 ? 0 : 1 ?>">
                                        <?php if($u['ESTADO'] == 1): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Bloquear usuario?');" style="border-radius: 0 4px 4px 0;">🚫 Bloquear</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('¿Activar usuario?');" style="border-radius: 0 4px 4px 0;">✅ Activar</button>
                                        <?php endif; ?>
                                    </form>
                                </div>

                                <!-- MODAL EDITAR USUARIO -->
                                <div class="modal fade text-start" id="modalEditarUsuario<?= $u['ID_USUARIO'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Editar Usuario #<?= $u['ID_USUARIO'] ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/usuarios/editar" method="POST" data-live="true" data-live-refresh="#usuarios" data-live-row="#fila_usuario_<?= $u['ID_USUARIO'] ?>">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_usuario" value="<?= $u['ID_USUARIO'] ?>">
                                                    <div class="mb-3">
                                                        <label>Nombre Completo</label>
                                                        <input type="text" name="nombre" id="edit_nombre_<?= $u['ID_USUARIO'] ?>" class="form-control" value="<?= htmlspecialchars($u['NOMBRE']) ?>" required oninput="generarUsuarioJS('edit_nombre_<?= $u['ID_USUARIO'] ?>', 'edit_user_<?= $u['ID_USUARIO'] ?>')">
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-6">
                                                            <label>Usuario (Para Login)</label>
                                                            <input type="text" name="username" id="edit_user_<?= $u['ID_USUARIO'] ?>" class="form-control fw-bold text-primary" value="<?= htmlspecialchars($u['USUARIO']) ?>" required>
                                                        </div>

                                                        <div class="col-6">
                                                            <label>Correo (Contacto)</label>
                                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['EMAIL']) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-6">
                                                            <label>Rol en el Sistema</label>
                                                            <select name="rol_id" class="form-select" required>
                                                                <?php foreach($roles as $rol): ?>
                                                                    <option value="<?= $rol['ID_ROL'] ?>" <?= $rol['ID_ROL'] == $u['ID_ROL'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['NOMBRE_ROL']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label>Sucursal Asignada</label>
                                                            <select name="sucursal_id" class="form-select" required>
                                                                <?php foreach($sucursales as $suc): ?>
                                                                    <option value="<?= $suc['ID_SUCURSAL'] ?>" <?= $suc['ID_SUCURSAL'] == $u['ID_SUCURSAL'] ? 'selected' : '' ?>><?= htmlspecialchars($suc['CIUDAD']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-secondary p-2 mt-3" style="font-size: 12px;">
                                                        <strong>🔑 Cambio de Contraseña:</strong> Déjalo en blanco si NO deseas cambiarla. Si escribes algo, esa será la nueva clave.
                                                    </div>
                                                    <div>
                                                        <input type="password" name="password" class="form-control" placeholder="Nueva Contraseña (Opcional)">
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== PESTAÑA ROLES ==================== -->
    <div class="tab-pane fade <?= (isset($_GET['tab']) && $_GET['tab'] == 'roles') ? 'show active' : '' ?>" id="roles">
        <div class="text-end mb-3">
            <button class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoRol">+ Registrar Nuevo Rol</button>
        </div>

        <div class="card shadow-sm border-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Rol del Sistema</th>
                        <th class="text-center">Vender y Atender</th>
                        <th class="text-center">Ver Cuadre de Caja</th>
                        <th class="text-center">Cambiar Precios / Descuentos</th>
                        <th class="text-center">Despacho Encomiendas</th>
                        <th class="text-center">Llegada / Entrega</th>
                        <th class="text-center">Anular y Devolver</th>
                        <th class="text-center">Módulo de Flota</th>
                        <th class="text-center">Módulo Usuarios</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($roles as $rol): ?>
                        <tr id="fila_rol_<?= $rol['ID_ROL'] ?>">
                            <td class="fw-bold fs-5 text-primary"><?= htmlspecialchars($rol['NOMBRE_ROL']) ?></td>
                            <td class="text-center"><?= $rol['P_VENTA'] ? '✅' : '❌' ?></td>
                            <td class="text-center"><?= $rol['P_CAJA'] ? '✅' : '❌' ?></td>
                            <td class="text-center"><?= ($rol['P_EDITAR_PRECIOS'] ?? 0) ? '🏷️' : '❌' ?></td>
                            <td class="text-center"><?= ($rol['P_DESPACHO'] ?? 0) ? '🚚' : '❌' ?></td>
                            <td class="text-center"><?= ($rol['P_LLEGADA'] ?? 0) ? '📦' : '❌' ?></td>
                            <td class="text-center"><?= $rol['P_ANULAR'] ? '🔴' : '❌' ?></td>
                            <td class="text-center"><?= $rol['P_FLOTA'] ? '🚌' : '❌' ?></td>
                            <td class="text-center"><?= $rol['P_USUARIOS'] ? '👥' : '❌' ?></td>
                            <td class="text-center">
                                <?php if($rol['ID_ROL'] == 1): ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Super Admin</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalPermisos<?= $rol['ID_ROL'] ?>">⚙️ Configurar</button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- MODAL EDITAR PERMISOS -->
                        <div class="modal fade text-start" id="modalPermisos<?= $rol['ID_ROL'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning text-dark">
                                        <h5 class="modal-title fw-bold">Privilegios: <?= htmlspecialchars($rol['NOMBRE_ROL']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="/suramerica2030/usuarios/guardarPermisos" method="POST" data-live="true" data-live-refresh="#roles" data-live-row="#fila_rol_<?= $rol['ID_ROL'] ?>">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_rol" value="<?= $rol['ID_ROL'] ?>">
                                            
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_venta" <?= $rol['P_VENTA'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">🛒 Vender Pasajes, Valores y Encomiendas</label>
                                            </div>
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_caja" <?= $rol['P_CAJA'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">💵 Ver y Gestionar Cuadre de Caja Diario</label>
                                            </div>
                                            <hr>
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_editar_precios" <?= ($rol['P_EDITAR_PRECIOS'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label text-primary fw-bold">🏷️ Cambiar Precios y Aplicar Descuentos</label>
                                                <small class="text-muted d-block" style="font-size: 13px;">Permite modificar el precio sugerido por el sistema en Pasajes, Sobrepeso, Encomiendas y Valores. Si se desactiva, el rol solo podrá cobrar las tarifas automáticas fijas calculadas por el sistema.</small>
                                            </div>
                                            <hr>
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_despacho" <?= ($rol['P_DESPACHO'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label">🚚 Despachar Encomiendas (Bodega ➔ Bus / En Tránsito)</label>
                                            </div>
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_llegada" <?= ($rol['P_LLEGADA'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label">📦 Registrar Llegada y Entrega de Encomiendas</label>
                                            </div>
                                            <hr>
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_anular" <?= $rol['P_ANULAR'] ? 'checked' : '' ?>>
                                                <label class="form-check-label text-danger fw-bold">🔴 Permitir Anular Ventas / Devolver Dinero</label>
                                            </div>
                                            <hr>
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_flota" <?= $rol['P_FLOTA'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">🚌 Acceso al Módulo de Flota y Rutas</label>
                                            </div>
                                            <div class="form-check form-switch mb-3 fs-5">
                                                <input class="form-check-input" type="checkbox" name="p_usuarios" <?= $rol['P_USUARIOS'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">👥 Acceso a Módulo de Usuarios</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer p-2">
                                            <button type="submit" class="btn btn-warning w-100 fw-bold">Aplicar Privilegios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== PESTAÑA SUCURSALES ==================== -->
    <div class="tab-pane fade <?= (isset($_GET['tab']) && $_GET['tab'] == 'sucursales') ? 'show active' : '' ?>" id="sucursales">
        <div class="text-end mb-3">
            <button class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevaSucursal">+ Registrar Nueva Sucursal</button>
        </div>

        <div class="card shadow-sm border-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>País</th>
                        <th>Ciudad</th>
                        <th>Dirección Física</th>
                        <th>Teléfono</th>
                        <th class="text-center">Comisión Oficina (%)</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sucursales as $suc): ?>
                        <tr id="fila_sucursal_<?= $suc['ID_SUCURSAL'] ?>">
                            <td><span class="badge bg-secondary">#<?= $suc['ID_SUCURSAL'] ?></span></td>
                            <td class="fw-bold"><?= htmlspecialchars($suc['PAIS']) ?></td>
                            <td class="text-primary fw-bold"><?= htmlspecialchars($suc['CIUDAD']) ?></td>
                            <td><?= htmlspecialchars($suc['DIRECCION']) ?></td>
                            <td class="text-secondary fw-bold">📞 <?= htmlspecialchars($suc['TELEFONO'] ?? 'S/N') ?></td>
                            <td class="text-center"><?= htmlspecialchars($suc['COMISION_EMPLEADO_PCT']) ?>%</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarSucursal<?= $suc['ID_SUCURSAL'] ?>">✏️ Editar</button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarSucursal<?= $suc['ID_SUCURSAL'] ?>">🗑️</button>
                                </div>

                                <!-- MODAL EDITAR SUCURSAL -->
                                <div class="modal fade text-start" id="modalEditarSucursal<?= $suc['ID_SUCURSAL'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Editar Sucursal</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/usuarios/editarSucursal" method="POST" data-live="true" data-live-refresh="#sucursales" data-live-row="#fila_sucursal_<?= $suc['ID_SUCURSAL'] ?>">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_sucursal" value="<?= $suc['ID_SUCURSAL'] ?>">
                                                    <div class="mb-2">
                                                        <label>País</label>
                                                        <input type="text" name="pais" class="form-control text-uppercase" required value="<?= htmlspecialchars($suc['PAIS']) ?>">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label>Ciudad</label>
                                                        <input type="text" name="ciudad" class="form-control text-uppercase" required value="<?= htmlspecialchars($suc['CIUDAD']) ?>">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label>Dirección</label>
                                                        <textarea name="direccion" class="form-control" rows="3" required><?= htmlspecialchars($suc['DIRECCION']) ?></textarea>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label>Teléfono</label>
                                                        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($suc['TELEFONO'] ?? '') ?>">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label>Comisión de Oficina (%)</label>
                                                        <input type="number" step="0.1" name="comision_empleado" class="form-control" value="<?= htmlspecialchars($suc['COMISION_EMPLEADO_PCT']) ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- MODAL ELIMINAR SUCURSAL -->
                                <div class="modal fade text-start" id="modalEliminarSucursal<?= $suc['ID_SUCURSAL'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title fs-6">Eliminar Sucursal</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/usuarios/eliminarSucursal" method="POST" data-live="true" data-live-refresh="#sucursales">
                                                <div class="modal-body text-center">
                                                    <input type="hidden" name="id_sucursal" value="<?= $suc['ID_SUCURSAL'] ?>">
                                                    <p class="mb-0">¿Eliminar la sucursal de <strong><?= htmlspecialchars($suc['CIUDAD']) ?></strong>?</p>
                                                    <small class="text-danger">Esto solo será posible si no hay operaciones registradas en ella.</small>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="submit" class="btn btn-danger w-100 fw-bold">Sí, Eliminar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== PESTAÑA CORREO (NUEVO) ==================== -->
    <div class="tab-pane fade <?= (isset($_GET['tab']) && $_GET['tab'] == 'email') ? 'show active' : '' ?>" id="email">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 border-top border-primary border-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-primary fw-bold">Configuración del Servidor de Envío (SMTP)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info" style="font-size: 13px;">
                            <strong>Aviso de Seguridad:</strong> Este es el correo desde el cual el sistema despachará los PDFs. 
                            Si usas Outlook o Gmail Empresarial, asegúrate de utilizar una <strong>"Contraseña de Aplicación"</strong> generada en los ajustes de seguridad de tu cuenta de correo, no tu contraseña habitual.
                        </div>
                        <form action="/suramerica2030/usuarios/guardarConfigCorreo" method="POST" data-live="true">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-bold text-secondary">Servidor Host (SMTP)</label>
                                    <select name="smtp_host" class="form-select border-primary" required>
                                        <option value="smtp.office365.com" <?= ($emailConfig['smtp_host'] == 'smtp.office365.com') ? 'selected' : '' ?>>Outlook / Office 365 (smtp.office365.com)</option>
                                        <option value="smtp.gmail.com" <?= ($emailConfig['smtp_host'] == 'smtp.gmail.com') ? 'selected' : '' ?>>Gmail (smtp.gmail.com)</option>
                                        <option value="mail.tu-dominio.com" <?= ($emailConfig['smtp_host'] == 'mail.tu-dominio.com') ? 'selected' : '' ?>>Dominio Propio (mail.dominio.com)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold text-secondary">Puerto Seguro</label>
                                    <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($emailConfig['smtp_port'] ?? 587) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-bold text-secondary">Correo Emisor (El que envía)</label>
                                    <input type="email" name="smtp_user" class="form-control text-primary fw-bold" value="<?= htmlspecialchars($emailConfig['smtp_user'] ?? '') ?>" placeholder="ej: envios@suramerica.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold text-secondary">Contraseña de Aplicación</label>
                                    <input type="password" name="smtp_pass" class="form-control" placeholder="************* (Dejar en blanco si no cambia)">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="fw-bold text-secondary">Nombre de la Empresa remitente</label>
                                <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($emailConfig['smtp_from_name'] ?? 'Suramerica 2030 Logistica') ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 fw-bold fs-5 shadow-sm">Guardar y Activar Servidor de Correo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVO USUARIO -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Nuevo Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/suramerica2030/usuarios/guardar" method="POST" data-live="true" data-live-refresh="#usuarios" data-live-reset="true">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre del Personal</label>
                        <input type="text" name="nombre" id="nuevo_nombre" class="form-control" required placeholder="Ej: Carlos Mendoza" oninput="generarUsuarioJS('nuevo_nombre', 'nuevo_usuario')">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label>Usuario (Generado Automático)</label>
                            <input type="text" name="username" id="nuevo_usuario" class="form-control fw-bold text-primary" required placeholder="carlos.mendoza">
                        </div>
                        <div class="col-6">
                            <label>Correo (Opcional pero recomendado)</label>
                            <input type="email" name="email" class="form-control" required placeholder="carlos@correo.com">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label>Rol</label>
                            <select name="rol_id" class="form-select" required>
                                <option value="">- Seleccionar -</option>
                                <?php foreach($roles as $rol): ?>
                                    <option value="<?= $rol['ID_ROL'] ?>"><?= htmlspecialchars($rol['NOMBRE_ROL']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label>Sucursal</label>
                            <select name="sucursal_id" class="form-select" required>
                                <option value="">- Seleccionar -</option>
                                <?php foreach($sucursales as $suc): ?>
                                    <option value="<?= $suc['ID_SUCURSAL'] ?>"><?= htmlspecialchars($suc['CIUDAD'] . ' - ' . $suc['PAIS']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label>Contraseña Temporal</label>
                        <input type="text" name="password" class="form-control" required value="suramerica<?= date('Y') ?>">
                        <small class="text-muted" style="font-size: 11px;">El usuario usará esta clave para su primer ingreso.</small>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="submit" class="btn btn-success w-100 fw-bold">Crear Cuenta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL NUEVO ROL -->
<div class="modal fade" id="modalNuevoRol" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Crear Nuevo Rol</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/suramerica2030/usuarios/guardarRol" method="POST" data-live="true" data-live-refresh="#roles" data-live-reset="true">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-bold">Nombre del Perfil/Rol</label>
                        <input type="text" name="nombre_rol" class="form-control" required placeholder="Ej: Auditor, Cajero Jr., Supervisor...">
                    </div>
                    
                    <h6 class="text-secondary border-bottom pb-1">Privilegios Iniciales</h6>
                    
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_venta">
                        <label class="form-check-label">🛒 Vender Pasajes, Valores y Encomiendas</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_caja">
                        <label class="form-check-label">💵 Ver y Gestionar Cuadre de Caja Diario</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_editar_precios">
                        <label class="form-check-label text-primary fw-bold">🏷️ Cambiar Precios y Aplicar Descuentos (Pasajes, Sobrepeso, Encomiendas, Valores)</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_despacho">
                        <label class="form-check-label">🚚 Despachar Encomiendas (Bodega ➔ Bus / En Tránsito)</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_llegada">
                        <label class="form-check-label">📦 Registrar Llegada y Entrega de Encomiendas</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_anular">
                        <label class="form-check-label text-danger fw-bold">🔴 Permitir Anular Ventas / Devolver Dinero</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_flota">
                        <label class="form-check-label">🚌 Acceso al Módulo de Flota y Rutas</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="p_usuarios">
                        <label class="form-check-label">👥 Acceso a Módulo de Usuarios</label>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">Crear Rol</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL NUEVA SUCURSAL -->
<div class="modal fade" id="modalNuevaSucursal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Registrar Sucursal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/suramerica2030/usuarios/guardarSucursal" method="POST" data-live="true" data-live-refresh="#sucursales" data-live-reset="true">
                <div class="modal-body">
                    <div class="mb-2">
                        <label>País</label>
                        <input type="text" name="pais" class="form-control text-uppercase" required placeholder="Ej: COLOMBIA">
                    </div>
                    <div class="mb-2">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad" class="form-control text-uppercase" required placeholder="Ej: BOGOTÁ">
                    </div>
                    <div class="mb-2">
                        <label>Dirección Completa</label>
                        <textarea name="direccion" class="form-control" rows="3" required placeholder="Terminal X, Local Y..."></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control" placeholder="Ej: 0991234567">
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">Crear Sucursal</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="/suramerica2030/public/assets/js/usuarios.js?v=<?= @filemtime(__DIR__ . '/../../../../public/assets/js/usuarios.js') ?: time() ?>"></script>