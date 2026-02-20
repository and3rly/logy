<template>
	<form @submit.prevent="guardar">
		<div class="alert alert-light fw-bold">
			Todos los campos marcados con <span class="text-danger">*</span> son obligatorios.
		</div>
		
		<div class="row mb-3">
			<label for="inputCodigo" class="col-sm-2 col-form-label fw-bold">
				Nombre: <span class="fw-bold text-danger">*</span>
			</label>
			<div class="col-sm-10">
				<input 
					id="inputNombre" 
					type="text" 
					class="form-control"
					v-model="form.nombre"
					required
				/>
			</div>
		</div>
		
		<div class="row mb-3">
			<label for="inputCodigo" class="col-sm-2 col-form-label fw-bold">
				Color: <span class="fw-bold text-danger">*</span>
			</label>
			<div class="col-sm-10">
				<div class="input-group">
					<select 
						class="form-select"
						v-model="form.etiqueta"
					>
						<option 
							v-for="i in colores" 
							:value="i.valor"
							>
							{{ i.nombre }}
						</option>
					</select>

					<span 
						class="input-group-text p-0"
						style="width: 45px;"
					>
						<div :class="'bg-' + form.etiqueta" style="width: 100%; height: 100%"></div>
					</span>

				</div>
			</div>
		</div>

		<div class="row" v-if="reg != ''">
			<label class="col-sm-2 col-form-label"></label>
			<div class="col-sm-9">
				<div class="form-check form-switch">
					<input
						class="form-check-input"
						type="checkbox"
						role="switch"
						id="chkActivo"
						:true-value="1"
						:false-value="0"
						v-model="form.activo"
					/>
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
	import Logy from "@/mixins/Logy.js"

	export default {
		name: "FormCategoria",
		mixins: [Logy],
		props: {
			categoria: {
				type: Object,
				default: null
			}
		},
		data: () => ({
			form: {},
			cat: {},
			colores: [
				{ nombre: 'Gris',           valor: 'secondary' },
				{ nombre: 'Blanco',         valor: 'light'     },
				{ nombre: 'Negro',          valor: 'dark'      },
				{ nombre: 'Celeste',        valor: 'info'      },
				{ nombre: 'Azul',           valor: 'primary'   },
				{ nombre: 'Morado',         valor: 'purple'    },
				{ nombre: 'Índigo',         valor: 'indigo'    },
				{ nombre: 'Rojo',           valor: 'danger'    },
				{ nombre: 'Rosa',           valor: 'pink'      },
				{ nombre: 'Naranja',        valor: 'warning'   },
				{ nombre: 'Amarillo',       valor: 'yellow'    },
				{ nombre: 'Lima',           valor: 'lime'      },
				{ nombre: 'Verde Oscuro',   valor: 'green'     },
				{ nombre: 'Verde',          valor: 'success'   }
			]
		}),
		created() {
			this.autoBuscar = false
			this._emit = true
			this.url = "mnt/categoria"

			if (this.categoria != null) {
				this.setDataForm(this.categoria)
			} else {
				this.fbase = {
					etiqueta: "light",
					activo: 1
				}
			}
		}
	}
</script>