<?php

/**
 * BDDB后台配置页面
 * @since   0.0.1
 * @version 0.8.6
 * 工具URL: http://wpsettingsapi.jeroensormani.com/
*/

//增加后台配置菜单
add_action( 'admin_menu', 'bddb_add_admin_menu' );
//设置描绘回调函数
add_action( 'admin_init', 'bddb_settings_init' );
//读取默认配置用
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');
//定义文件宏
define('BDDB_OPTION_FILE_NANE', 'wp-boycott-douban-db/bddb-options.php');

	/**
	 * @brief   追加后台菜单
	 * @since	  0.0.1
   * @version 0.9.6
	*/
function bddb_add_admin_menu() {
  $myicon = "iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAYAAAD0eNT6AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAB7kSURBVHhe7d0JtCx1fSdwOiKg4r6iRDDCaBwJLnFFAwgT1ESOCxgX4owbiijRoxAdnWhiyHGN0cHlqEQ9HjUB3OKouB0woIZEwO2IG6IYIqMGEEF0XHq+/666yfX53rv3vlvVXdX/z+ec3/nVvy7v+a5dXf9v/6u7eicAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAICRm7Sdjkyn06PT/qAZ0bF/nkwmJ7bbMDh5/h+XdkgzomNfz/P/Oe02HRAAOpYTQDlAX96M6NhJOQE8o92Gwcnz/0VpL2xGdOy0PP+PbLfpwG+0HQCoiAAAABUSABgTl6wAOiIAAECFBAAAqJAAwJi4BADQEQEAACokADAmVgAAOiIAAECFBAAAqJAAwJi4BADQEQEAACokAABAhQQAxsQlAICOCAAAUCEBAAAqJAAwJi4BAHREAACACgkAjIkVAICOCAAAUCEBAAAqJAAwJi4BAHREAACACgkAAFAhAYAxcQmAoXOMMhoCAABUSABgTLy6AuiIAADQHSGV0RAAAKBCAgBj4tUVQ+cYZTQEAACokAAA0B0rAIyGAAAAFRIAALpjBYDREAAAoEICAEB3rAAwGgIAAFRIAADojhUARkMAAIAKCQAAUCEBAKA7LgEwGgIAAFRIWu3YdDp9TtrLmxEde8tkMnlCu92pPG43Sds3tWfqeqnd2766yr7dUj9u6+ptbF+R+lbqovx7f5rOJuSx+c2026VumbpuW+Xx2Fr/RWrlcbhm1fbqfZenyuPz7a4fn/xbX5p2QjOiY6fl8Tqy3aYDAkDHBIBebSoA5LHZOW3/VJno99mi3zzVh0tSF6VmgWBVPye/S5mQiDw2+6XdIVUm+i1rl1RfyuPz7VR5XGahoO3n5vH59/QNEQB6JQB0TADomADQqw0HgDwe90p7QOrgtkoIGIrPpM5eqfxul5WdNcjjcp+0A1ZVXwFsM85PndVWeXwuLTu3J7/Xy9KOb0Z0TADomADQMQGgV2sGgPz//9tpK5N9mfjL0v5YlAlnJRB8IL9rWa5eCnlcDkw7KLUy4Zfl+rH5YmolEJTJ6Odl52oCQK8EgI55EyCjl5Pu/qkXpy7I8Mup16aOSI1p8i/umnpG6u9TV+b3OTV1VKq892B08u++e+olqW9keGbqRan/lhrj5F+UyxRPS70rdXV+r3ekHlJ+sIoXVYyGAMAo5cS7T+q5qa9m+LnUC1J3LD9bEuVSRQkxb0/9KL/ne1P/I3Wj8sOhyr/vjqkXpMpj8tnUn6ZuX362ZMr7Eh6T+of8rpelXpdaxt+TJSatdiwnAZcA+nNqqrzKL5cByrv1a3VK6nWTyeSTzXDxctw/Lu0pqfvOdtTrytQNmk065hJAx6wAMCblyf9nqZon/+KRqTMz6X489fBm12Lkf/+YVAllb0vVPvkXJn9GQwCA8Tok9e5MwOenntTs6l/+t3ZNHZ+6OMPXpZbp0gtUQwCA8btL6k1lQk6Va+69yN99w1R5I993U+Xd7uUGPcBICQDd874KFqVMyOVd9+elHtTs6kb+vnJ9/+upF6ZuXPYB4yYAwPIpHyf8UCbtk1Pl9rk7LH/+/qnyEb43pIZ4sx5gBwkAsLzKpyW+lgn82Ga4fvkzN02V+yn8Y6rcxAdYMgIALLfyrvSTMpmfkVrXu/TbwFCW+8tNb4AlJQBAHcpteD+Vyf1ZzfDX5WflVf97snlSynV+WHICANTlrzPJvzP1K59Xz/jQtHLnvofNdgBLTwCA+jw6dW4m/XIfgTL5ly+v+Vhq7zIG6iAAQJ32SZU7CZZ79pfP9AOVEQCgbvu3HaiMAAAAFRIAAKBCAgAAVEgAAIAKCQAAUCEBAAAqJAAAQIUEAACokAAAABUSAACgQgIAAFRIAACACgkAAFAhAQAAKiQAAECFBAAAqJAAAAAVEgAAoEICAABUSAAAgAoJAABQIQEAACokAABAhQQAAKiQAAAAFRIAAKBCAgAAVEgAAIAKCQAAUCEBAAAqJAAAQIUEAACokAAAABUSAACgQgIAAFRIAACACgkAAFAhAQAAKiQAAECFBAAAqJAAAAAVEgAAoEICAABUSAAAgAoJAABQIQEAACokAABAhQQAAKjQpO10ZDqdHp/2smbEnH0+dV5bF6Qua+snqeumbrGqfi/14NTNUtTrl6lTUh9NfbetS1Nl/w1TN2r7nVL3buu3UszfaZPJ5Mh2mw4IAB0TAObu06l3pt6Rk8MVsz0bkMfrwLSHp46b7aAW/5R6U+qUHDdXzfasU46Ze6SV4+ZRqbuXfcyFANAxlwAYq3LyvltOCAekXrsjk3+RP/fJ1J9kc5/UG2c7WXbPz2N+n9TfpjY0+Rf5M/+SekXqdzMsYeD1sx/AyAgAjM1Jqb1y8j06dX6za/Pyd12Yeko2ywn9wtlOls3Zqf3yOP9VM9y8/F2fTT0tm/un3j3bCSMhADAmL8nJ9hmpi9tx58oJPa0s754128Gy+FjqsDy+X2qG3crf+4XUEdksK1MwCgIAYzKX96zkRH5J2kGp98x2MHYfSD0wj+uPm2GvvtF2GDwBALYik0V5F/iTU9+c7WCszs9jeXj7eAKrCACMyVw/tZJJo3yE8OhmxEg9u+3AFgQA2I6EgE+klY92Mj7lPSNntNvAFgQAxmRR9614deryZpORKJ/qeF67DWyFAABryETyszSf9R6Xk9sObIMAwJgs8s6VAsC4CACwBgEA1mEymfxr2unNiIF7ax6v77XbwDYIAIzJor+7ws2BxuHUtgPbIQDA+pVbyTJ85QuigDUIAIzJolcASgAobwhkuMqNf3boi6GgNgIArFMmlnI3OV8UNGyfajuwBgGAMVn0CkBxZdsZps+1HViDAAAb86O2M0zfbzuwBgGAMbECwFp+0HZgDQIAbIwVgGGzAgDrJAAwJkNYAbh+2xkmKwCwTgIAbIwAMGy7tx1YgwDAmFgBYC03aTuwBgEANkYAGDYBANZJAGBMhrACcLO2M0w3bTuwBgEA1mk6nd4m7RbNiIFa9ArAEEIqrIsAAOu3X9sZrvu1HViDAADr9zttZ7gObfuiWAFgNAQAWL/7tJ3h2mM6nd6j3V4EAYDREABgHTKplDf/PbQZMXCLXgWAURAAYH0e2XaG76i2L4IVAEZDAID1EQDG407T6fTYdhvYBgEA1pDJ5PFpBzYjRuJ5edwWcX6zAsBoCACwHZlEyr3lT2xGjEi5Z8Nzm01gawQA2L5XpvZoNhmZExdwKcAKAKMhAMA2ZPJ4VdrRzYiROimP4xPb7XkQABgNAQC2IpPG2WnPbEaM3JvzeB7XbgMtAQBWyURx19RZ2Tyg2cOSeHUe19NTd27HfbECwGgIABCZGPZNlSX/81LuJ7+cDkt9MY/zCSnf6kj1BACqlUng5qmjUhdl+LWUJf86vDT1/Tzu728f/92a3Z2wAsBoCAAsvZzg90zdN/XY1AtTb099Jj/6Xurtqb3Lf0d1Dk+Vx/+HOR4+kiqXCY5JHZzyyQ+WnrTasZw4jk97WTOiYxemzkj9PLXLVmrXVb1U+Qz/XqmdU7BRV7b1w1W1Mr5W6tpbVDmf3qEtunfaZDI5st2mAwJAxwQAgF4IAB1zCQAAKiQAAECFBAAAqJAAAAAVEgAAoEICAABUSAAAgAoJAABQIQEAACokAABAhQQAAKiQAAAAFRIAAKBCAgAAVEgAAIAKCQAAUCEBAAAqJAAAQIUEAACokAAAABUSAACgQgIAAFRIAACACgkAAFAhAQAAKiQAAECFBAAAqJAAAAAVEgAAoEICAABUSAAAgAoJAABQIQEAACokAABAhQQAAKiQAAAAFRIAAKBCAgAAVEgAAIAKCQAAUCEBAAAqJAAAQIUEAACokAAAABUSAACgQgIAAFRIAACACgkAAFAhAQAAKiQAAECFBAAAqJAA0L1p2wFgsAQAAKiQAAAAFRIAAKBCAgAAVEgAAIAKCQAAUCEBAAAqNGk7HZlOp89Je3kzoiOr762wte21fl5sb/tnqWtSP1lVK+M9U/ukGJ9zU+ekrpfabZ21S2pLK+fJ1efLLfdt9Gds3GmTyeTIdpsOOBhhOxLo/jztz5oRI3NyJowntdvAFlwCgO0TkoGlJAAAy0p4g+0QAACgQgIAbJ9XkePlsYPtEAAAoEICAGyfV5HAUhIAAKBCAgBsnxUAYCkJAABQIQEAts8KALCUBAAAqJAAAAAVEgBg+1wCAJaSAAAAFRIAYPusAABLSQAAgAp5dcPcTafT30y7XeqWqeu2db1t9F+kftzWNau2V++7PPWt1Lcnk8lP0zuTf+tL005oRozMW3I8PKHd7lSOi5uk7ZvaM1WO1d3bvrrKvt1SK8fq1dvYviJVjt+Luj5+YXsEAHqRE+R+aXdIlYl+y9ol1ZdLUt9OlRPqLBS0/dycXP89fUMEgFHbVADIY79z2v6pMtHvs0W/eaoP5fi9KDULBKv6OfldSliAzggAdCIny/ukHbCq+jpBbsb5qbPaOjsn1EvLzu3J7/WytOObESOz4QCQx/teaQ9IHdxWCQFD8ZnU2SuV3+2yshN2lADADsmJ8sC0g1IrE35Zrh+bL6ZWAsFpOaH+vOxcTQAYtTUDQB7f305bmezLxF+W9seiBNqVQPCB/K7lchismwDAuuVkefe0I1NHpG5f9i2R/5c6LfV3OZF+YLYn8ju/PO05zYiR2WoAyGNalvXLMVzqjmXfEijh9X2p95ee3/uqshO2RwBgu3KyLCfIlZNlOXHWoLyp8O9Sr0w9NSUAjNN/BIAcx+XafTmGH5/6L2XfklsdBsqbDOHXCABsVU6Yj0t7Suq+sx31ujJ1g2aTkTk1dUGqhIDybv1anZJ6XYLAJ5shNAQAfkUm/mPSjksty9Io0PhEqgSB9zRDaicAUCb9XdOekSoTf/mMPrC8Ppd6bYLAm5shtRIAKpaJ/4Zpz0qVif/GZR9Qje+kShAo97qgQgJApTL5l+v7L04N8fP6wPyUjxM+P0Hgw82QWggAlcnEf/+0MvGXz/EDrPjbVAkCa94gi+UgAFQiE/9N0/4i9bTZDoBfVz718j8TAl7bDFlmAkAFMvkfm1Ze9bvOD6zHmamyGvDpZsgyEgCWWPuq/02ph812AGzMsxIC/qbdZskIAEsqk/+haWXy33u2A2DHvCv11ASBcnmAJfIbbWeJZPIvX17zsZTJH9isR6fOzXnlkGbIsrACsETyBN097Y2p8oQF6NrzJpPJS9ptRk4AWBKZ/MvH+8rk7xa+QJ/KrYSPShDw9cMjJwAsgUz+f5j23tTOsx0A/SqfDnhEQoB7BoyY9wCMXCb/P0or319v8gfmpXxL6Mdz/rlDM2SMBIARy5OvfM1p+d56gHn7r6kSAu7RDBkbAWCk8qQr3953cjMCWIg9UyUElI8dMzICwAjlyfanaa9pRgALdYPUx3JeekQzZCwEgJHJk6zcz9/HcIChOS3np/KeJEbCpwBGJE+uZ6e9ohkBDNKDJpPJ6e02AyYAjEQm/8enla/rBBiyH6cOTQj4TDNkqASAEcjkf3ja+5sRwOB9N1VCwJebIUMkAAxcJv/7pX0itctsB8A4fCVVQsAlzZChEQAGLJP/ndLK5H+r2Q6AcTknVULAVc2QIfEpgIHK5F8m/VNTJn9grO6VOq3ZZGgEgOF6Q6qsAACM2WF5QfOidpsBcQlggPJkKTf68Vl/YJn4eODACAADk8n/wLQzmxHA0rgodbeEgCuaIYvmEsCAZPLfNe11zQhgqdwu5fw2IALAsJQnh+v+wLJ6dF7oHNdus2AuAQxEnhRPTntjMwJYavecTCb/0m6zIALAAGTy3yftgtTOsx0Ay+3MBICD220WxCWAYfjzlMkfqMVBeeHzxHabBbECsGB5Ejw47YPNCKAa5RbB+0wmk580Q+bNCsDilVf/ALW5Tcr5b4GsACxQXv0/M+1VzQigSvtPJpMvtNvMkQCwIJn8b572jdQNZjsA6vS+BICHtdvMkUsAi1OWvkz+QO0emhdER7TbzJEVgAXIwX63tHObEUD1zplMJvdut5kTKwCLcUzbAdhpp3vlhdEftNvMiRWAOctBvmfad5oRAK0PTSYTIWCOrADMn1f/AL/uwXmB5DLAHAkAc5SDe5c0AQBg657WduZAAJivMvnfuNkEYAt/nBdK5btRmAMBYL68+gfYvmPbTs+8CXBOkmoflfauZgTANlyTuuFkMvlZM6QvVgDm59FtB2DbrpN6RLNJnwSAOcir/5umHd6MAFiDADAHAsB8OJgB1u/heeG0e7tNTwSA+RAAANavzE3Omz0TAHqWFFu+8/r3mxEA6yQA9EwA6J+DGGDjHpIXUDdrt+mBANA/AQBgxzh/9kgA6FHS615pv9eMANigB7SdHggA/Tqg7QBs3D3bTg8EgH7dt+0AbNze0+n0tu02HRMA+mUFAGBz7tV2OiYA9CSp9UZpd2lGAOwglwF6IgD0x6t/gM2zAtATAaA/rv8DbJ4A0BMBoD9WAAA2b5fpdCoE9GDSdjqUg7UEq5+krj3bwTz9MnVK6qOp77Z1aarsv2GqvDej9Dul7t3Wb6Woz+dT57V1Qeqytspz97qpW6yqcj+PB6fcmW4xnjSZTE5ut+mIANCDBIB90r7ejJiTf0q9KXVKThRXzfasUx6ve6QdmHpU6u5lH0vr06l3pt6R4+SK2Z4NyLFSjpOHp46b7WBe/jKP1/9qt+mIANCDnCQOTftYM2IOnp+Tw1+125uSx+53056QOma2g2VRwuHrc5yc3ww3J8fJ7dNOSB0920HfSmA7qt2mI94D0I/btZ1+nZ3ar6vJv8jf9dnU07K5f+rds52M2UmpvfKYHp3qZPIv8nddmHpKNsvq0YWznfRp77bTIQGgHwJA/8oKy2E5CX+pGXYrf+8XUkdks7xyZJxeksfwGamL23Hn8nd/Nq1cFjhrtoO+OKf2QADoh7Tarw+kHpiT74+bYa++0XbGZy6XOHMcXpJ2UOo9sx304dbT6fQ67TYdEQD6Ia325/yccA9PlXf1wyC0x+OTU9+c7aAPXlh1TADohwO1P89uO6xlrm9yTggoHyH0psD+eGHVMQGgY9Pp9Hppt2pGdKxc0z2j3YbByfH5ibTjmxEd26vtdEQA6F65aQjdK++6fl67DeuxqI85vzp1ebNJh8rNmeiQANA9B2k/3AWMUUhQ/Vna65sRHSqrq3RIAOieANAPAYCNWuSNzgSA7jm3dkwA6J6U2r235lXV99ptGLwcr/+adnozoiMCQMcEgO45SLt3atthIxZ9q3M3B+qWF1cdEwC6JwB0r3yBC4xNuVU13XFu7ZgA0D0ptVvlxj8b/tY2iEWvAJQAUN4QSDcEgI4JAN1zkHbrU22HUUlwLXcH9EVB3fHiqmMCQPcEgG59ru2wUYteASiubDubt2vb6YgA0L1p2+nG99sOY/SjtrN5V7edjggA3ZvHN9TV5Adth42yArBcnFs7JgB0z0HaLSsAjJkVgO44t3ZMAOieg7RbVgDYUUNYAbh+29k859aOCQDdc5B2a/e2wxgJAN3xHoCOCQDdEwC6dZO2w0ZZAVguzq0dEwC65yDtlgDAmAkA3XFu7ZgA0D0Habdu2nYYo5u1nc1zbu2YANA9B2m3Fr0CMIRlZHbMQh+76XR6m7RbNCM64COVHRMAuvd/20437td2GJvfaTvdcFvljgkAHZtMJuWdqhc3IzpwaNsXxQoAO2q/ttMNAaBjAkA/vtp2Nm+P6XR6j3Z7EQQAdtR92s7mXZMXV99pt+mIANCPr7Wdbix6FQA2JKG1vPnvoc2IDnj13wMBoB9WALp1VNsXwQoAO+KRbacbAkAPBIB+WAHo1p3yiurYdhvGQADolgDQAwGgH1YAuve8hIBFHK9WANiQHKePTzuwGdERAaAHAkAPJpPJt9KuaUZ0pHym+rnNJgxTJv/y3RUnNiM69I220yEBoD9WAbp34gIuBVgBYCNemdqj2aRDX2g7HRIA+vPpttOtkxICnthuw2DkuHxV2tHNiA5dMJlMLm236ZAA0J+z2k733pyT7XHtdt+sALCmHI9npz2zGdExL6Z6IgD0RwDo16tz0j09ded23BcBgG3K8XfXVHmuH9DsoQefajsdEwB6MplMLkm7oBnRk8NSX8wJ+ISUb11jbnK87ZsqS/7npXxfRb+sAPREAOiXVYD5eGnq+zkhvz91VGq3ZncnrAAwk+Pq5u3xdVGG5V4flvz79295MeUN1T0RAPolAMzX4am3p36Yk/RHUuUywTGpg1Pemc265FjZM3Xf1GNTL0y9PfWZ/Oh7qXJ87V3+O+bC8n+PvLrpUU4ae6WVewIwDOX7xEv9cFWtjK+VuvYWVZ4fd2iL8Sk3jzkj9fPULlupXVf1UuUz/OU5u3OKYXjWZDL5m3abjgkAPUsIKEuF+zYjADbgLgkAn2+36ZhLAP37YNsBWL/Pm/z7JQD077S2A7B+72s7PXEJYA6m0+lX0lxHBlg/y/89swIwH1YBANbP8v8cCADzIQAArJ/l/zkQAOYgSfZzaeWOYQCs7b1tp0cCwPxYBQBYm+X/OREA5kcAAFjb29pOz3wKYI6m0+kpaUc2IwC2UG63fOvJZPKLZkifrADMl1taAmzba0z+82MFYM6m0+l70h7WjABoXZ0qr/7L93MwB1YA5q98hzgAv6q8+jf5z5EVgAWYTqf/kPaQZgRA7JEAcGm7zRxYAVgMqwAA/6m8+jf5z5kVgAWZTqflWwIf3IwAqrZPAsCF7TZzYgVgcXwiAGCnnU40+S+GFYAFmk6n/zvt6c0IoDrfTO2bAPDLZsg8CQALlABwnbQvp/ae7QCoy1GZ/N/RbjNnLgEsUA78a9JOaEYAVfmgyX+xrAAMwHQ6Lfe+flwzAqjC3RMAfEvqAlkBGIayCnBZswmw9F5p8l88KwADMZ1On5j25mYEsLQuSZU3/pVLoCyQFYCByJPh5LTyPQEAy+ypJv9hEACGpawCfLXZBFg6L87k/3/abRbMJYCBmU6nB6Sd3YwAlsZHMvk/sN1mAKwADEyeIJ9Ke0ozAlgKl6eOaTYZCgFggBIC3pj2mmYEMHrH5Lx2UbvNQLgEMGDT6fTjaYc0I4BR+utM/s9utxkQAWDAEgBum3ZO6lazHQDjcmYm/4PbbQbGJYAByxPn4rT/nvrFbAfAeHw+dUSzyRBZARiB6XT6oLQPNSOAwftO6pC8iPl6M2SIrACMQJ5EH057SDMCGLSrUkeY/IdPABiJPJnKzTMe1owABuvInK/+ud1mwASAEcmT6n1prqkBQ/WYnKdOb7cZOAFgZPLkenfao5oRwGA8Peend7XbjIAAMEJ5kv192mObEcDCHZvz0mvbbUbCpwBGbDqd/l7aO1J7znYAzN8fZfI/pd1mRASAkUsI2CuthIDyJUIA8/KD1CMz+Z/RDBkblwBGLk++b6fdP/XO2Q6A/l2QOtjkP24CwBLIk3CaKu8JeEmzB6A3/5gqk/+XmiFj5RLAkplOp09Ne30zAuhU+RRSWfb/ZTNkzKwALJk8Md+QdlhKOge69IKcX8od/kz+S8IKwBKbTqevSPM1nMBmlBcT5TP+n2yGLAsBYMklBPx+2itTd57tAFi/sqJYJn/fSLqEBIBKWA0ANuCKVJn4y0eMWVICQEWsBgDrUL54rEz+5SPGLDFvAqxIntAfTe2XzRelrp7tBGh8NfXHOUc8xORfBysAlZpOpzdOe07qhNTOZR9QpR+m/jKTfrlMSEUEgMolCNwy7fiU9wdAfcolwRMz+V/eDKmJAMBMgsBt00oQePpsB7DMyq3Dy6v+cktfKiUA8CsSBPZJ+5PUY1I3KfuApfG21Fsy8ftMPwIA25Yw8Oi0EgT+cLYDGKMvp96Semsm/vINfjAjALCmBIHylcMlCJTyEUIYh/Id/WXS/3AzhF8lALAhCQMHppUgcGSqfJIAGI6vpN6VKsv835ntgW0QANhhCQMHpZWbC5UvH7pb2QfM3dmpD5bKpP/F2R5YBwGATiQMlE8RrISBUtdPAd37WepDqZVJ/9/KTtgoAYBeJBAcklYCwT1Td0ndKAVsXLlr5/mp81Jnpj6USf+n6bApAgBzkUBQPl64f6qEgZXaMwX8pytTZbJfmfDPz2Rfvo4XOicAsDAJBeUuhCthYO/UrVN7rKprpWDZXJW6OFXepLfSL0yVyd6NeZgbAYDBagPClqGg1PVSu62zdkltaeW437IXO/ozhmPa9mJr22v9vNjedrkGf03qJ6tqW+Pvp8ok/x8Tfib5y9IBAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACA+dlpp/8PPUXOhs8yzewAAAAASUVORK5CYII=";
  add_menu_page( 'bddb设定', 'bddb设定', 'manage_options', __FILE__, 'bddb_options_page', 'data:image/svg+xml;base64,' . $myicon );
  add_submenu_page(__FILE__, '书设定', '书设定', 'manage_options', __FILE__.'&tab=tab_book', 'bddb_options_page');
  add_submenu_page(__FILE__, '影设定', '影设定', 'manage_options', __FILE__.'&tab=tab_movie', 'bddb_options_page');
  add_submenu_page(__FILE__, '游戏设定', '游戏设定', 'manage_options', __FILE__.'&tab=tab_game', 'bddb_options_page');
  add_submenu_page(__FILE__, '专辑设定', '专辑设定', 'manage_options', __FILE__.'&tab=tab_album', 'bddb_options_page');
}


	/**
	 * @brief   添加渲染用的组件
	 * @since	  0.0.1
   * @version 0.8.6
	*/
function bddb_settings_init(  ) {
	$arg = array(
		'sanitize_callback' => 'BDDB_Settings::sanitize_options',
		'default' => BDDB_Settings::default_options(),
	);
	register_setting( 'bddb_settings_group', 'bddb_settings', $arg);

  add_settings_section(
  'bddb_pluginPage_section',
  '总体设定',
  'bddb_settings_section_callback',
  'bddb_option_tab'
  );

  add_settings_section(
  'bddb_movie_section',
  '电影选项',
  'bddb_settings_section_callback',
  'bddb_movie_tab'
  );

  add_settings_section(
  'bddb_book_section',
  '书籍选项',
  'bddb_settings_section_callback',
  'bddb_book_tab'
  );
  
  add_settings_section(
  'bddb_game_section',
  '游戏选项',
  'bddb_settings_section_callback',
  'bddb_game_tab'
  );
  
  add_settings_section(
  'bddb_album_section',
  '专辑选项',
  'bddb_settings_section_callback',
  'bddb_album_tab'
  );

  //目录设定·待更改
  add_settings_field(
  'basic_folder_setting',
  '目录设定',
  'bddb_basic_setting_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );
  
  //总体图片设置
  add_settings_field(
  'basic_poster_setting',
  '图片设定',
  'bddb_poster_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );

  //顺序设置·待更改
  add_settings_field(
  'basic_order_setting',
  '共通顺序设定',
  'bddb_general_order_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );

  add_settings_field(
    'special_function',
    '特殊功能',
    'bddb_special_function_render',
    'bddb_option_tab',
    'bddb_pluginPage_section'
    );

  //08
	//-1
	
	add_settings_field(
		'bddb_m_omdb_key',
		'OMDB Auth KEY',
		'bddb_m_omdb_key_render',
		'bddb_movie_tab',
		'bddb_movie_section'
	);
  
  add_settings_field(
    'bddb_m_misc_map',
    '特殊图标列表',
    'bddb_all_misc_map_render',
    'bddb_movie_tab',
    'bddb_movie_section',
    array('type'=>'movie')
    );

  add_settings_field(
  'bddb_b_max_serial_count',
  '系列书籍有效本数',
  'bddb_b_max_serial_count_render',
  'bddb_book_tab',
  'bddb_book_section'
  );

  add_settings_field(
  'bddb_b_countries_map',
  '国名缩写对照表',
  'bddb_b_countries_map_render',
  'bddb_book_tab',
  'bddb_book_section'
  );

  add_settings_field(
  'bddb_b_poster_setting',
  '封面',
  'bddb_b_poster_render',
  'bddb_book_tab',
  'bddb_book_section'
  );

  add_settings_field(
    'bddb_b_misc_map',
    '特殊图标列表',
    'bddb_all_misc_map_render',
    'bddb_book_tab',
    'bddb_book_section',
    array('type'=>'book')
    );
  
  add_settings_field(
  'bddb_g_giantbomb_key',
  'GiantBomb Auth KEY',
  'bddb_g_giantbomb_key_render',
  'bddb_game_tab',
  'bddb_game_section'
  );
  
  add_settings_field(
  'bddb_g_poster_setting',
  '游戏海报',
  'bddb_g_poster_render',
  'bddb_game_tab',
  'bddb_game_section'
  );
  
  add_settings_field(
    'bddb_g_misc_map',
    '特殊图标列表',
    'bddb_all_misc_map_render',
    'bddb_game_tab',
    'bddb_game_section',
    array('type'=>'game')
    );

  add_settings_field(
  'bddb_a_poster_setting',
  '专辑规格',
  'bddb_a_poster_render',
  'bddb_album_tab',
  'bddb_album_section'
  );

  add_settings_field(
  'bddb_a_language_define',
  '专辑语种',
  'bddb_a_language_define_render',
  'bddb_album_tab',
  'bddb_album_section'
  );

  add_settings_field(
  'bddb_a_test_setting',
  '专辑预留',
  'bddb_test_field_render',
  'bddb_album_tab',
  'bddb_album_section'
  );

}

function bddb_basic_setting_render(	 ) {
  //03
	global $global_option_class;
	$options = $global_option_class->get_options();
  ?>
	<span>当前TAX版本号：</span>
	<input type='text' name='bddb_settings[tax_version]' readonly='readonly' size='24' value='<?php echo $options['tax_version']; ?>'/><br />
	<span>当前TYPE版本号：</span>
	<input type='text' name='bddb_settings[type_version]' readonly='readonly' size='24' value='<?php echo $options['type_version']; ?>'/><br />
	<?php
}


function bddb_basic_setting_render1(  ) {
  //03
	global $global_option_class;
	$options = $global_option_class->get_options();
  ?>
  <span>	图片缓存路径：</span>
  <input type='text' name='bddb_settings[default_folder]' size='24' value='<?php echo $options['default_folder']; ?>'/><br />
  <span>	默认排序：</span>
  <select name="bddb_settings[primary_common_order] size=20" id="id_primary_common_order">
  <?php
	$strs = array("1111","2222","3333","4444");
	foreach ($strs as $str) {
		echo sprintf("\n\t<option value='%s'>%s</option>", $str, $str);
	}
  ?>
  </select>
  <?php
}

/**
 * @brief	  渲染misc的有图标对应项。
 * @param   array   $args
 *                  type  =>  book,movie,game,album
 * @since	  0.6.5
*/
function bddb_all_misc_map_render($args) {
  if (!is_array($args)|| !array_key_exists('type', $args)) {
    return;
  }
  $type = $args['type'];
  if (!BDDB_Statics::is_valid_type($type)) {
    return;
  }
  $misc_key = substr($type,0,1).'_misc_map';
  $option_key = 'bddb_settings['.$misc_key.']';
  global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>slug用半角分号分割：</span><br />
	<textarea rows='4' cols='40' name='<?php echo $option_key; ?>' ><?php echo $options[$misc_key]; ?></textarea>
<?php
}

function bddb_poster_render() {
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<span>每页缓存海报数：</span><input type='text' name='bddb_settings[thumbnails_per_page]' size='24' value='<?php echo $options['thumbnails_per_page']; ?>'/></br>
	<span>图像宽度：</span><input type='text' name='bddb_settings[poster_width]' size='24' value='<?php echo $options['poster_width']; ?>'/><br />
  <span>图像高度：</span><input type='text' name='bddb_settings[poster_height]' size='24' value='<?php echo $options['poster_height']; ?>'/><br />
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width]' size='24' value='<?php echo $options['thumbnail_width']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height]' size='24' value='<?php echo $options['thumbnail_height']; ?>'/></br>
<?php
}

function bddb_general_order_render() {
	global $global_option_class;
	$options = $global_option_class->get_options();
	$t = new BDDB_Common_Template();
	$option_value = '';
	for($i=0;$i<10;$i++){
		$sel_list .= sprintf("\n\t<option value='%02d'>%02d</option>",$i,$i);
	}
  /*
  TBD
	foreach ($options['general_order'] as $key=>$ci) {
		$value = $ci['priority'];
		if (empty($value)) {
			$value = false;
		}
		printf('<span>%1$s:</span><select name="bddb_settings[general_order][%1$s] size=20" id="id_general_common_order_%1$s" value="%2$s">%3$s</select></br>',
		$key, $value, $sel_list);
	}*/
}

function bddb_special_function_render() {
  $type = 'game';
  //$all_bddbs = get_post()
  ?>
  <textarea rows='4' cols='40' name='bddb_special_functon_disp_area' ><?php echo "1234567"; ?></textarea>
  <?php
}


function bddb_m_omdb_key_render()
{
  //08
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<input type='text' name='bddb_settings[m_omdb_key]' size='64' value='<?php echo $options['m_omdb_key']; ?>'/>
<?php
}

/**
 * @brief	渲染书籍封面和缩略图规格输入项。
 * @since	  0.6.2
*/
function bddb_b_poster_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定书籍封面宽度和高度，false为与总体设定一致，建议比例1：1.40：</span><br />
  <span>封面宽度：</span><input type='text' name='bddb_settings[poster_width_book]' size='24' value='<?php echo $options['poster_width_book']; ?>'/></br>
  <span>封面高度：</span><input type='text' name='bddb_settings[poster_height_book]' size='24' value='<?php echo $options['poster_height_book']; ?>'/></br>
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width_book]' size='24' value='<?php echo $options['thumbnail_width_book']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height_book]' size='24' value='<?php echo $options['thumbnail_height_book']; ?>'/></br>
<?php
}

function bddb_b_max_serial_count_render()
{
  //08
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<input type='text' name='bddb_settings[b_max_serial_count]' size='64' value='<?php echo $options['b_max_serial_count']; ?>'/>
<?php
}

/**
 * @brief	渲染图书国名缩写输入项。
 * @since	  0.5.3
*/
function bddb_b_countries_map_render() {
  global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>缩写与全名间用半角逗号分割，国名间用半角分号分割：</span><br />
	<textarea rows='4' cols='40' name='bddb_settings[b_countries_map]' ><?php echo $options['b_countries_map']; ?></textarea>
<?php
}

/**
 * @brief	渲染游戏海报和缩略图规格输入项。
 * @since	  0.6.2
*/
function bddb_g_poster_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定游戏海报宽度和高度，false为与总体设定一致，建议比例1：1.42：</span><br />
  <span>海报宽度：</span><input type='text' name='bddb_settings[poster_width_game]' size='24' value='<?php echo $options['poster_width_game']; ?>'/></br>
  <span>海报高度：</span><input type='text' name='bddb_settings[poster_height_game]' size='24' value='<?php echo $options['poster_height_game']; ?>'/></br>
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width_game]' size='24' value='<?php echo $options['thumbnail_width_game']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height_game]' size='24' value='<?php echo $options['thumbnail_height_game']; ?>'/></br>
<?php
}


function bddb_g_giantbomb_key_render()
{
  //08
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<input type='text' name='bddb_settings[g_giantbomb_key]' size='64' value='<?php echo $options['g_giantbomb_key']; ?>'/>
<?php
}

/**
 * @brief	渲染专辑封面和缩略图规格输入项。
 * @since	  0.6.2
*/
function bddb_a_poster_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定专辑图片宽度和高度，false为与总体设定一致，建议比例1：1：</span><br />
  <span>海报宽度：</span><input type='text' name='bddb_settings[poster_width_album]' size='24' value='<?php echo $options['poster_width_album']; ?>'/></br>
  <span>海报高度：</span><input type='text' name='bddb_settings[poster_height_album]' size='24' value='<?php echo $options['poster_height_album']; ?>'/></br>
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width_album]' size='24' value='<?php echo $options['thumbnail_width_album']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height_album]' size='24' value='<?php echo $options['thumbnail_height_album']; ?>'/></br>
<?php
}

/**
 * @brief	定义语种情报
 * @since	  0.8.6
*/
function bddb_a_language_define_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定专辑语种。格式为000-语言，多种语言合并用半角逗号【,】分割；多个语言用半角分号【;】分割</span><br />
  <textarea rows='6' cols='40' name='bddb_settings[a_languages_def]' ><?php echo $options['a_languages_def']; ?></textarea>

<?php
}

	/**
	 * @brief	section渲染时的回调函数，根据section id显示不同的文字。
	 * @param	array	$section			section
	 * @since	  0.0.1
	 * @version	0.6.2
	*/
function bddb_settings_section_callback( $section ) {

  switch ($section['id']) {
    case 'bddb_book_section':
      echo '<span>书籍相关设定</span>';
      break;
    case 'bddb_movie_section':
      echo '<span>电影相关设定</span>';
      break;
    case 'bddb_game_section':
      echo '<span>游戏相关设定</span>';
      break;
    case 'bddb_album_section':
      echo '<span>专辑相关设定</span>';
      break;
    case 'bddb_pluginPage_section':
      default:
        echo '<span>一些基本设定项目，某些可以被子项目覆盖</span>';
        break;
  }
}

function bddb_options_page(	 ) {
	global $global_option_class;
	$global_option_class = new BDDB_Settings();
		if( isset( $_GET[ 'tab' ] ) ) {
			$active_tab = $_GET[ 'tab' ];
		} else {
			$active_tab = 'tab_option';
		}
		?>
	<div id="bddb_page_content" class="wrap bddb-option" >
  <h1><span>B</span>oycott <span>D</span>ouban <span>D</span>ata<span>b</span>ase</h1>
  <div class="description">This is description of the page.</div>
			<h2 class="nav-tab-wrapper">
				<a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_option" class="nav-tab <?php echo $active_tab == 'tab_option' ? 'nav-tab-active' : ''; ?>">基本功能</a>
				<a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_movie" class="nav-tab <?php echo $active_tab == 'tab_movie' ? 'nav-tab-active' : ''; ?>">影片设定</a>
				<a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_book" class="nav-tab <?php echo $active_tab == 'tab_book' ? 'nav-tab-active' : ''; ?>">书籍设定</a>
				<a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_game" class="nav-tab <?php echo $active_tab == 'tab_game' ? 'nav-tab-active' : ''; ?>">游戏设定</a>
				<a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_album" class="nav-tab <?php echo $active_tab == 'tab_album' ? 'nav-tab-active' : ''; ?>">专辑设定</a>
			</h2>
	 <form action='options.php' method='post'>
  <?php
  settings_fields( 'bddb_settings_group' );
  switch($active_tab) {
	  case 'tab_option':
	  default:
		do_settings_sections( 'bddb_option_tab' );
		break;
	  case 'tab_movie':
		do_settings_sections( 'bddb_movie_tab' );
		break;
	  case 'tab_book':
		do_settings_sections( 'bddb_book_tab' );
		break;
	  case 'tab_game':
		do_settings_sections( 'bddb_game_tab' );
		break;
	  case 'tab_album':
		do_settings_sections( 'bddb_album_tab' );
		break;
  }
  submit_button();
  ?>

  </form>
</div>
  <?php

}

function bddb_test_field_render() {
	?>
	<span>WP->is_ssl = <?php echo is_ssl()? 'YES':'NO'; ?> "wp_http_supports( array( 'ssl' ) )" = <?php echo wp_http_supports( array( 'ssl' ) )?'YES':'NO'; ?> </span>
	<?php
}

?>
