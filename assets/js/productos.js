let modalProducto;
let modoEdicion = false;

        document.addEventListener('DOMContentLoaded', function() {
            modalProducto = new bootstrap.Modal(document.getElementById('modalProducto'));
            
            // Búsqueda al presionar Enter
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if(e.key === 'Enter') {
                    buscarProductos();
                }
            });
        });

        function abrirModalCrear() {
            modoEdicion = false;
            document.getElementById('modalTitle').textContent = 'Registrar Nuevo Producto';
            document.getElementById('formProducto').reset();
            document.getElementById('id_producto').value = '';
            document.getElementById('estado').checked = true;
            modalProducto.show();
        }

        function editarProducto(id) {
            modoEdicion = true;
            document.getElementById('modalTitle').textContent = 'Editar Producto';
            
            // Cargar datos del producto
            fetch(`ajax/productos.php?action=obtener&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const producto = data.producto;
                        document.getElementById('id_producto').value = producto.id_producto;
                        document.getElementById('codigo').value = producto.codigo;
                        document.getElementById('nombre').value = producto.nombre;
                        document.getElementById('descripcion').value = producto.descripcion || '';
                        document.getElementById('id_categoria').value = producto.id_categoria;
                        document.getElementById('id_marca').value = producto.id_marca;
                        document.getElementById('id_medida').value = producto.id_medida;
                        document.getElementById('precio_compra').value = producto.precio_compra;
                        document.getElementById('precio_venta').value = producto.precio_venta;
                        document.getElementById('stock_minimo').value = producto.stock_minimo;
                        document.getElementById('estado').checked = producto.estado == 1;
                        modalProducto.show();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'No se pudo cargar el producto', 'error');
                });
        }

      function guardarProducto() {
  const form = document.getElementById('formProducto');
  if (!form.checkValidity()) { form.reportValidity(); return; }

  const formData = new FormData(form);
  const action = modoEdicion ? 'actualizar' : 'guardar';

  fetch(`ajax/productos.php?action=${action}`, { method: 'POST', body: formData })
    .then(async (response) => {
      let data;
      try { data = await response.json(); }
      catch {
        const text = await response.text();     // si llegara HTML por algún warning
        throw new Error(text.slice(0, 300));
      }
      if (!response.ok || (data && data.success === false)) {
        throw new Error(data && data.message ? data.message : 'Error en el servidor');
      }
      return data;
    })
    .then((data) => {
      Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message || 'OK', timer: 1800, showConfirmButton: false })
        .then(() => location.reload());
      modalProducto.hide();
    })
    .catch((err) => {
      console.error(err);
      Swal.fire('Error', 'Ocurrió un error al guardar el producto', 'error');
    });
}

        function eliminarProducto(id, nombre) {
            Swal.fire({
                title: '¿Está seguro?',
                html: `Se eliminará el producto:<br><strong>${nombre}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id_producto', id);
                    
                    fetch('ajax/productos.php?action=eliminar', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Ocurrió un error al eliminar el producto', 'error');
                    });
                }
            });
        }

        function buscarProductos() {
const termino = document.getElementById('searchInput').value || '';

fetch(`ajax/productos.php?action=buscar&termino=${encodeURIComponent(termino)}`)
    .then(async (response) => {
    if (!response.ok) {
        // intenta leer JSON de error; si no, texto
        let msg = 'Error desconocido';
        try {
        const j = await response.json();
        msg = j.message || msg;
        } catch {
        msg = await response.text();
        }
        throw new Error(msg);
    } return response.json();
    })
    .then((data) => {
      // Si el backend devuelve {success:false,...}
    if (data && !Array.isArray(data) && data.success === false) {
        Swal.fire('Error', data.message || 'Ocurrió un error en la búsqueda', 'error');
        return;
    }
    actualizarTabla(Array.isArray(data) ? data : []);
    })
    .catch((error) => { console.error('Error:', error);
    Swal.fire('Error', 'Ocurrió un error en la búsqueda', 'error');
    });
}

        function filtrarCategoria(idCategoria) {
            // Actualizar tabs activos
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            const url = idCategoria 
                ? `ajax/productos.php?action=filtrar&categoria=${idCategoria}`
                : `ajax/productos.php?action=listar`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    actualizarTabla(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Ocurrió un error al filtrar', 'error');
                });
        }

        function actualizarTabla(productos) {
            const tbody = document.getElementById('productosBody');
            
            if(productos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="text-center py-5">
                            <i class="fas fa-box-open fs-1 text-muted mb-3 d-block"></i>
                            <p class="text-muted">No se encontraron productos</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = productos.map(producto => `
                <tr>
                    <td><strong>${producto.codigo}</strong></td>
                    <td>${producto.nombre}</td>
                    <td><span class="text-muted">${(producto.descripcion || '').substring(0, 50)}${(producto.descripcion || '').length > 50 ? '...' : ''}</span></td>
                    <td>${producto.categoria_nombre || 'Sin categoría'}</td>
                    <td>${producto.marca_nombre || 'Sin marca'}</td>
                    <td>${producto.unidad_medida || 'N/A'}</td>
                    <td><strong>S/ ${parseFloat(producto.precio_compra).toFixed(2)}</strong></td>
                    <td><strong>S/ ${parseFloat(producto.precio_venta).toFixed(2)}</strong></td>
                    <td>${producto.stock_minimo}</td>
                    <td>
                        ${producto.estado == 1 
                            ? '<span class="badge badge-success"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disponible</span>'
                            : '<span class="badge badge-danger"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Inactivo</span>'
                        }
                    </td>
                    <td class="text-center">
                        <button class="btn-action btn-edit" onclick="editarProducto(${producto.id_producto})" title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-action btn-delete" onclick="eliminarProducto(${producto.id_producto}, '${producto.nombre.replace(/'/g, "\\'")}');" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
