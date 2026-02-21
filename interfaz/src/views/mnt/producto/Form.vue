<template>
	<div class="alert alert-light fw-bold">
		Todos los campos marcados con <span class="text-danger">*</span> son obligatorios.
	</div>

	<form @submit.prevent="guardar">
		<div class="row">
			<div class="col-sm-8">
				<div class="row g-2">
					<div class="mb-2 col-sm-6">
						<label for="inputCodigo" class="form-label fw-bold">
							Código: <span class="text-danger">*</span>
						</label>
						<input 
							type="text" 
							class="form-control" 
							v-model="form.codigo"
							required
						/>
					</div>

					<div class="mb-2 col-sm-6">
						<label for="inputCodigoBarra" class="form-label fw-bold">
							Código de barra:
						</label>
						<input 
							type="text" 
							class="form-control"
							v-model="form.codigo_barra"
						/>
					</div>
				</div>	

				<div class="row g-2">
					<div class="mb-2 col-sm-12">
						<label for="inputNombre" class="form-label fw-bold">
							Nombre: <span class="text-danger">*</span>
						</label>
						<input 
							type="text" 
							class="form-control"
							v-model="form.nombre"
							required 
						/>
					</div>
				</div>	

				<div class="row g-2">
					<div class="mb-2 col-sm-6">
						<label for="selectTipoProducto" class="form-label fw-bold">
							Tipo producto: <span class="text-danger">*</span>
						</label>
						<select 
							name="selectTipoProducto" 
							id="selectTipoProducto"
							class="form-select"
							v-model="form.tipo_producto"
						>
							<option :value="null">Seleccione tipo de producto...</option>
							<option v-for="i in cat.tipos" :value="i">{{ i }}</option>
						</select>
					</div>

					<div class="mb-2 col-sm-6">
						<label for="selectMarca" class="form-label fw-bold">
							Marca: <span class="text-danger">*</span>
						</label>
						<select 
							name="selectMarca" 
							id="selectMarca"
							class="form-select"
							v-model="form.marca_id"
						>
							<option :value="null">Seleccione marca...</option>
							<option v-for="i in cat.marcas" :value="i.id">{{ i.nombre }}</option>
						</select>
					</div>

					<div class="mb-2 col-sm-6">
						<label for="selectUnidadMedida" class="form-label fw-bold">
							Unidad de medida: <span class="text-danger">*</span>
						</label>
						<select 
							name="selectUnidadMedida" 
							id="selectUnidadMedida"
							class="form-select"
							v-model="form.unidad_medida_id"
						>
							<option :value="null">Seleccione unidad de medida...</option>
							<option v-for="i in cat.unidades" :value="i.id">{{ i.nombre }}</option>
						</select>
					</div>

					<div class="mb-2 col-sm-6">
						<label for="selectCategoria" class="form-label fw-bold">
							Categoría: <span class="text-danger">*</span>
						</label>
						<select 
							name="selectCategoria" 
							id="selectCategoria"
							class="form-select"
							v-model="form.categoria_id"
						>
							<option :value="null">Seleccione categoría...</option>
							<option v-for="i in cat.categorias" :value="i.id">{{ i.nombre }}</option>
						</select>
					</div>
				</div>	
			</div>
			<div class="col-sm-4 mb-2">
				<div class="card h-100">
					<div class="card-header bg-white fw-bold">
						Imagen
					</div>
					<div class="card-body">
						<div class="col-sm-12 text-center">
							<Imagen
								:img="imgActual"
							></Imagen>
						</div>

						<label 
							for="file-upload" 
							class="btn btn-lime d-flex align-items-center justify-content-center d-grid gap-2 mt-1"
						> 
						<template v-if="reg == '' || this.producto == null">
							<i class="fas fa-plus"></i> Subir imagen
						</template>

						<template v-else>
							<i class="fas fa-plus"></i> Cambiar imagen
						</template>

					</label>
					<input 
						id="file-upload" 
						class="form-control" 
						type="file"
						@change="subirImagen"
					/>            
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="mb-2 col-sm-4">
				<label for="inputCosto" class="form-label fw-bold">
					Costo:
				</label>
				<input 
					type="number"
					step="0.01"
					class="form-control"
					v-model="form.costo"
				/>
			</div>
			<div class="mb-2 col-sm-4">
				<label for="inputNombre" class="form-label fw-bold">
					Precio:
				</label>
				<input 
					type="number"
					step="0.01"
					min="0"
					class="form-control"
					v-model="form.precio"
				/>
			</div>

			<div class="mb-2 col-sm-4">
				<label for="inputNombre" class="form-label fw-bold">
					Existencia mínima:
				</label>
				<input 
					type="number"
					step="0.01"
					min="0"
					class="form-control"
					v-model="form.existencia_minima"
				/>
			</div>

			<div class="col-sm-12 mb-3">
				<label for="selectCategoria" class="form-label fw-bold">
					Descripción: <span class="text-danger">*</span>
				</label>
				<div class="overflow-hidden w-100 position-relative">
					<quill-editor 
						theme="snow" 
						style="min-height: 100px;"  
						v-model:content="form.descripcion"
						contentType="html"
					/>
				</div>
			</div>

			<div class="col-sm-2">
				<div class="form-check form-switch">
					<input 
						class="form-check-input" 
						type="checkbox" 
						role="switch" 
						id="chkVence" 
						:true-value="1"
						:false-value="0"
						v-model="form.control_vence"
					>
					<label class="form-check-label" for="chkVence">Control vence</label>
				</div>
			</div>

			<div class="col-sm-2">
				<div class="form-check form-switch">
					<input 
						class="form-check-input" 
						type="checkbox" 
						role="switch" 
						id="chkActivo" 
						:true-value="1"
						:false-value="0"
						v-model="form.activo"
					>
					<label class="form-check-label" for="chkActivo">Activo</label>
				</div>
			</div>
		</div>

		<div class="col-sm-12 text-end mt-4">
			<button
				v-if="reg != ''"
				type="button" 
				class="btn btn-secondary me-2" 
				@click="$emit('cerrar')"
				:disabled="btnGuardar"
			>
				<i class="fas fa-times me-1"></i>Cancelar
			</button>
			<button 
				type="submit" 
				class="btn btn-theme"
				:disabled="btnGuardar"
			>
				<span 
					v-if="btnGuardar"
					class="spinner-border spinner-border-sm" 
					aria-hidden="true"
				></span>
				<i v-else class="fas fa-save me-1"></i>
				
				{{ !btnGuardar ? 'Guardar' : 'Guardando...'}}
			</button>
		</div>
	</form>

</template>

<script>
	import quillEditor from "@/components/plugins/QuillEditor.vue"
	import Imagen from "@/components/general/Imagen.vue"
	import Logy from "@/mixins/Logy.js"

	export default {
		name: "FormProducto",
		mixins: [Logy],
		props: {
			producto: {
				type: Object,
				default: null
			}
		},
		data: () => ({
			urlFoto: null,
			cat: {}
		}),
		created() {
			this.autoBuscar = false
			this.formEspecial = true
			this.url = "mnt/producto"

			this.getDatos()

			if (this.producto != null) {
				this.setDataForm(this.producto)
			} else {
				this.fbase = {
					tipo_producto: "B",
					marca_id: null,
					categoria_id: null,
					unidad_medida_id: null,
					activo: 1,
					control_venta: 0
				}
			}

		},
		methods: {
			getDatos() {
				this.inicio = true

				this.$http
				.get(`${this.$baseUrl}/${this.url}/get_datos`)
				.then(res => {
					this.inicio = false
					this.cat = res.data.cat
				}).catch(e => {
					this.inicio = false
					console.log(e)
				});
			},
			subirImagen(f) {
				if (f.target.files[0]) {
					let tmp = f.target.files[0]
					this.urlFoto = tmp
					this.form.foto = tmp
				}
			}
		},
		computed: {
			imgActual() {
				return this.urlFoto || (this.producto?.foto ? this.producto.foto : null)
			}
		},
		components: {
			quillEditor,
			Imagen
		}
	}
</script>