/**
 * Created by Administrator on 2015/8/9.
 */

$2(function(){
    var next = $2('table tr');
    var i =1;
    var ID = 'ID';
    for(i=1;i<26;i++){
        var td =
            '<td>'+'<a href="javascript:;">'+ID+i+'</a>'+'<span class="r_words" id='+ID+i+'>'+'</span>'+'</td>';
        next.append(td);
    }
    var searchBtn = $2('.btn9527');
    searchBtn.click(function(){

        $2.ajax({
            type : 'POST',
            url : '/search.php',
            data : {name:'search'},
            success :function(msg){
                var Objname = $2.parseJSON(msg);
                for(var j=1;j<26;j++){
                    var key='ID'+j;
                    if(Objname[key]=='Y'){
                         $2('#ID'+j).html('<font color="blue">在线</font>');
                     }else{
                         $2('#ID'+j).html('<font color="red">离线</font>');
                    }

                }
            }
        },'json')
    })

});

// alert(typeof(Objname));
    
