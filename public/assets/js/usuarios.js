/**
 * SURAMERICA 2030 - Módulo de Gestión de Usuarios y Permisos
 */

function generarUsuarioJS(idNombre, idUsuario) {
    const inputNombre = document.getElementById(idNombre);
    const target = document.getElementById(idUsuario);
    if (!inputNombre || !target) return;

    let val = inputNombre.value.trim().toLowerCase();
    let nombreLimpio = val.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    let partes = nombreLimpio.split(' ').filter(Boolean);
    let usuarioSugerido = "";
    if (partes.length >= 2) {
        usuarioSugerido = partes[0] + '.' + partes[1]; 
    } else if (partes.length === 1) {
        usuarioSugerido = partes[0]; 
    }
    target.value = usuarioSugerido;
}
