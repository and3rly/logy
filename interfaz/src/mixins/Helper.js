import numeral from 'numeral'

export default {
  name: "Helper",
  data: () => ({

  }),
  methods: {
    formatoFecha(fecha, tipo, eshora) {
      if (fecha) {
        fecha = fecha.split(" ");
        var y=0, m=0, d=0, h=0, i=0, s=0, aux=[];

        if (fecha[0]) {
          aux = fecha[0].split("-");
          y = (aux[0] || 0);
          m = (aux[1] || 0);
          d = (aux[2] || 0);
        }

        if (fecha[1]) {
          aux = fecha[1].split(":");
          h = (aux[0] || 0);
          i = (aux[1] || 0);
          s = (aux[2] || 0);
        }
        
        if (eshora) {
          aux = fecha[0].split(":");
          h = (aux[0] || 0);
          i = (aux[1] || 0);
          s = (aux[2] || 0);
        }

        switch(tipo) {
          case 1:
            return [d,m,y].join('/');
          case 2:
            return [d,m,y].join('/')+" "+[h,i].join(':');
          case 3:
            return [d,m,y].join('-')+" "+[h,i,s].join(':');
          case 4:
            return [d,m,y].join('-');
          case 6:
            return [d,m,y].join('/')+" "+[h,i].join(':');
          case 7:
            return [y,m,d].join('-')+"T"+[h,i].join(':');
          case 8:
            return [h,i].join(':');
          case 9:
            return [y,m,d].join('-');
          default:
            return [d,m,y].join('/')+" "+[h,i].join(':');
        }
      }

      return null;
    },
    formatoNumero(numero, formato) {
      if (!formato) {
        return numeral(numero).format('0')
      }
      return numeral(numero).format(formato)
    }
  }
}