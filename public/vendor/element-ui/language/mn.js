(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('element/locale/mn', ['module', 'exports'], factory);
  } else if (typeof exports !== "undefined") {
    factory(module, exports);
  } else {
    var mod = {
      exports: {}
    };
    factory(mod, mod.exports);
    global.ELEMENT.lang = global.ELEMENT.lang || {};
    global.ELEMENT.lang.mn = mod.exports;
  }
})(this, function (module, exports) {
  'use strict';

  exports.__esModule = true;
  exports.default = {
    el: {
      colorpicker: {
        confirm: 'Болсон',
        clear: 'Цэвэрлэх'
      },
      datepicker: {
        now: 'Одоо',
        today: 'Өнөөдөр',
        cancel: 'Цуцлах',
        clear: 'Цэвэрлэх',
        confirm: 'Болсон',
        selectDate: 'Огноо сонгох',
        selectTime: 'Цаг сонгох',
        startDate: 'Эхлэх огноо',
        startTime: 'Эхлэх цаг',
        endDate: 'Дуусах огноо',
        endTime: 'Дуусах цаг',
        prevYear: 'Өмнөх жил',
        nextYear: 'Дараа жил',
        prevMonth: 'Өмнөх сар',
        nextMonth: 'Дараа сар',
        year: 'жил',
        month1: '1 сар',
        month2: '2 сар',
        month3: '3 сар',
        month4: '4 сар',
        month5: '5 сар',
        month6: '6 сар',
        month7: '7 сар',
        month8: '8 сар',
        month9: '9 сар',
        month10: '10 сар',
        month11: '11 сар',
        month12: '12 сар',
        week: 'долоо хоног',
        weeks: {
          sun: 'Ням',
          mon: 'Дав',
          tue: 'Мяг',
          wed: 'Лха',
          thu: 'Пүр',
          fri: 'Баа',
          sat: 'Бям'
        },
        months: {
          jan: '1 сар',
          feb: '2 сар',
          mar: '3 сар',
          apr: '4 сар',
          may: '5 сар',
          jun: '6 сар',
          jul: '7 сар',
          aug: '8 сар',
          sep: '9 сар',
          oct: '10 сар',
          nov: '11 сар',
          dec: '12 сар'
        }
      },
      select: {
        loading: 'Ачаалж байна',
        noMatch: 'Тохирох өгөгдөл олдсонгүй',
        noData: 'Өгөгдөл байхгүй',
        placeholder: 'Сонгох'
      },
      cascader: {
        noMatch: 'Тохирох өгөгдөл олдсонгүй',
        loading: 'Ачаалж байна',
        placeholder: 'Сонгох',
        noData: 'Өгөгдөл байхгүй'
      },
      pagination: {
        goto: 'Шилжих',
        pagesize: '/хуудас',
        total: 'Нийт {total}',
        pageClassifier: ''
      },
      messagebox: {
        title: 'Мессеж',
        confirm: 'Болсон',
        cancel: 'Цуцлах',
        error: 'Буруу өгөгдөл'
      },
      upload: {
        deleteTip: 'Устгахын тулд delete дарна уу',
        delete: 'Устгах',
        preview: 'Урьдчилан харах',
        continue: 'Үргэлжлүүлэх'
      },
      table: {
        emptyText: 'Өгөгдөл байхгүй',
        confirmFilter: 'Баталгаажуулах',
        resetFilter: 'Дахин тохируулах',
        clearFilter: 'Бүгд',
        sumText: 'Нийт'
      },
      tree: {
        emptyText: 'Өгөгдөл байхгүй'
      },
      transfer: {
        noMatch: 'Тохирох өгөгдөл олдсонгүй',
        noData: 'Өгөгдөл байхгүй',
        titles: ['Жагсаалт 1', 'Жагсаалт 2'],
        filterPlaceholder: 'Түлхүүр үг оруулах',
        noCheckedFormat: '{total} зүйл',
        hasCheckedFormat: '{checked}/{total} сонгогдсон'
      },
      image: {
        error: 'АМЖСАНГҮЙ'
      },
      pageHeader: {
        title: 'Буцах'
      },
      popconfirm: {
        confirmButtonText: 'Тийм',
        cancelButtonText: 'Үгүй'
      },
      empty: {
        description: 'Өгөгдөл байхгүй'
      }
    }
  };
  module.exports = exports['default'];
});
