export default {
  name: 'Logy',
  data: () => ({
    url: "",
    reg: "",
    form: {},
    fbase: {},
    bform: {},
    lista: [],
    _key: "id",
    tiempo: 0,
    _emit: false,
    inicio: true,
    termino: "",
    scrollTo: true,
    btnBuscar: false,
    autoBuscar: true,
    btnGuardar: false,
    _blqconfirm: false,
    inicioArray: false,
    formEspecial: false,
    buscarPstSave: false
  }),
  mounted () {
    if (this.autoBuscar) {
      this.buscar()
    }

    this.form = {...this.form, ...this.fbase}
  },
  methods: {
    guardar () {
      let continuar = true

      if (this._blqconfirm === false) {
        continuar = confirm("¿Está seguro de guardar?")
      }

      if (continuar) {
        this.btnGuardar = true

        let datos = this.form

        if (this.formEspecial) {
          datos = new FormData()
          for (let i in this.form) {
            datos.append(i, this.form[i])
          }
        }

        this.$http
        .post(`${this.$baseUrl}/${this.url}/guardar/${this.reg}`, datos)
        .then(result => {
          let res = result.data

          if (res.exito) {
            this.$toast.success(res.mensaje)
            
            if (this.reg === "") {
              this.limpiar()
            }

            if (this._emit === true) {
              
              if (this.reg !== "" && this._key !== "") {
                this.reg = res.linea[this._key]
              }

              this.$emit("actualizar", res.linea)
              
            } else {
              if (this.buscarPstSave) {
                this.buscar()
              } else {

                if (res.linea) {
                  if (this.reg === "") {
                    if (this.inicioArray) {
                      this.lista.unshift(res.linea)
                    } else {
                      this.lista.push(res.linea)
                    }
                  }  else {
                    let tmp = this.lista.filter(e => {                    
                      return e[this._key] == this.reg
                    })[0];


                    if (tmp) {
                      let key = this.lista.indexOf(tmp)

                      for (let i in res.linea) {
                        this.lista[key][i] = res.linea[i]
                      }
                    }
                  }
                }
              }
            }
          } else {
            this.$toast.error(res.mensaje)
          }
          this.btnGuardar = false
        })
        .catch(e => {
          console.log(e)
          this.btnGuardar = false
        });
      }
    },
    buscar () {
      this.inicio    = true
      this.btnBuscar = true

      this.$http
      .get(`${this.$baseUrl}/${this.url}/buscar`, {params: this.bform})
      .then(result => {
        let res = result.data

        this.btnBuscar = false
      
        if (res.lista) {
          this.lista  = res.lista
          this.tiempo = res.tiempo ?? 0
          
        } else {
          this.lista  = []
          this.tiempo = 0

          if (res.mensaje) {
            console.log(res.mensaje)
          }
        }

        this.grupos = res.grupos ?? []

        this.inicio = false;

      })
      .catch(e => {
        this.btnBuscar = false
        this.inicio    = false
        console.log(e)
      });
    },
    limpiar () {
      this.reg  = ''
      this.form = {}
      this.form = {...this.form, ...this.fbase}
    },
    editar (idx) {
      let tmp = this.filtrada[idx]

      this.reg  = tmp.id
      this.form = tmp

      if (this.scrollTo) {
        window.scrollTo(0,0)
      }
    },
    setDataForm(obj) {
      this.reg = obj[this._key]
      
      for (let i in obj) {
        this.form[i] = obj[i]
      }
    },
    setDataRegistro(key, obj) {
      if (this.reg === "") {
        if (this.inicioArray) {
          this.lista.unshift(obj)
        } else {
          this.lista.push(obj)
        }
      } else {
        for (let i in obj) {
          this[key][i] = obj[i]
        }
      }
    },
    setDatoSelect(lista, campo, valor) {
      let opciones = []
      
      lista.forEach(e => {
        opciones.push({
          value: e[campo],
          label: e[valor]
        })
      })

      return opciones
    }
  },
  computed: {
    filtrada() {
      return this.lista.filter(o => {
        if (this.termino === '') {
          return true;
        } else {
          let res = false
          let ter = this.termino.toLowerCase()

          for (let i in o) {
            if (typeof o[i] === 'string' && o[i].toLowerCase().includes(ter)) {
              res = true
            } else if (o[i] == ter) {
              res = true
            }
          }

          return res
        }
      })
    }
  }
}