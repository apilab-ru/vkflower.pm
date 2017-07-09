//"use strict";
angular.module('db', ['ngResource']).
    factory('db', function ($resource) {
        var db = {};
        db.flovers = [
            {
                color : 'red',
                name  : 'Красная роза',
                title : 'красная',
                type  : 'flower'
            },
            {
                color : 'white',
                name  : 'Белая роза',
                title : 'белая',
                type  : 'flower'
            }
        ];
        
        db.stickers = [
            {
                name : 'Будь моей женой',
                id   : 1,
                type : 'sticker'
            },
            {
                name : 'I vove You',
                id   : 2,
                type : 'sticker'
            },
            {
                name : 'Для тебя',
                id   : 3,
                type : 'sticker'
            }
        ];
        
        db.orderFlovers = [];
        
        return db;
    });

angular.module('flowers', ['db','ngDraggable']).
  config(function($routeProvider) {
    $routeProvider.
      when('/', {controller:main.mainFlowers, templateUrl:'/templates/mainFlowers.html'}).
      otherwise({redirectTo:'/'});
  });
  
/*function mainFlowers($scope,db)
{
  
}*/

function main($scope,db){
    
    $scope.flowers      = db.flovers;
    $scope.stickers     = db.stickers;
    $scope.orderFlovers = db.orderFlovers;
    $scope.orderText    = "";
    
    $scope.hoverRose = false;
    $scope.current   = false;

    $scope.tab = 'stickers';

    $scope.orderSet = {
        spare : 0
    };
    
    var self = this;
    this.offset = {x:0,y:0};
    
    this.mainFlowers = function(){
        self.offset.x = $('.holst').offset().top;
        self.offset.y = $('.holst').offset().left;
    }
    
    /*$scope.spray = function(myb){
        console.log($scope.orderSet.spare);
        if($scope.orderSet.spare){
            $scope.orderSet.spare = 0;
        }else{
            $scope.orderSet.spare = 1;
        }
    }*/
    
    $scope.textOrder = function(){
        var text = "";
        var countRose = 0;
        var countSticker = 0;
        $.each($scope.orderFlovers,function(n,i){
            countRose ++;
            if(i.sticker){
                countSticker ++;
            }
        });
        
        if(countRose){
            text += "цветов: "+countRose+" ";
        }
        if(countSticker){
            text += "стикеров: " + countSticker + " ";
        }
        
        if ($scope.orderSet.spare) {
            text += "опрыскать цветы";
        }
        
        return text;
    }
    
    $scope.getPrice = function(){
        var price = 0;
        if($scope.orderSet.spare){
            price += 100;
        }
        $.each($scope.orderFlovers,function(n,i){
            price += 150;
            if(i.sticker){
                price += 50;
            }
        })
        return price;
    }
    
    $scope.onDropComplete = function($data,$event){
        
        //console.log('data',$data,$event);
        
        if($data.type == 'flower'){
            if($data.order){
                $data.x = self.calcOffsetX($event.x);
                $data.y = self.calcOffsetY($event.y);
            }else{
                $scope.orderFlovers.push({
                    color : $data.color,
                    name  : $data.name,
                    x     : self.calcOffsetX($event.x),
                    y     : self.calcOffsetY($event.y),
                    order : 1,
                    type  : 'flower'
                });
                
                $scope.current = $scope.orderFlovers[ $scope.orderFlovers.length - 1 ];
            }
        }else{
            //console.log($data);
        }
    }
    
    this.calcOffsetX = function(x){
        return x - 77;
    }
    
    this.calcOffsetY = function(y){
        return y - 45;
    }
    
    $scope.checkSelect = function($data){
        if($scope.current != false && $scope.current.$$hashKey == $data.$$hashKey){
            return 'check';
        }
    }
    
    $scope.checkCurrent = function(){
        if($scope.current == false){
            return false;
        }else{
            return true;
        }
    }
    
    $scope.clearSelect = function(){
        $scope.current = false;
    }
    
    $scope.onDropRose = function($data,$event,$rose){
        if($data.type == 'sticker'){
            $rose.sticker = $data.id;
        }
    }
    
    $scope.selectRose = function($data){
        $scope.current = $data;
    }
    
    $scope.checkColor = function(color){
        if($scope.current && $scope.current.color == color){
            return 'checked';
        }
    }
    
    $scope.deleteSticker = function(){
        $scope.current.sticker = false;
    }
    
    $scope.deleteRose = function(){
        self.deleteFilteredItem($scope.current.$$hashKey,$scope.orderFlovers);
    }
    
    this.deleteFilteredItem = function (hashKey, sourceArray) {
        angular.forEach(sourceArray, function (obj, index) {
            if (obj.$$hashKey === hashKey) {
                sourceArray.splice(index, 1);
                return;
            };
        });
    }
    
    $scope.checkSticker = function(flower){
        if(flower.sticker){
            return true;
        }
    }
    
    $scope.createOrder = function(){
        html2canvas(document.getElementById('photoOrder')).then(function (canvas) {
            canvas.toBlob(function (blob) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "/save.php", true);
                var formData = new FormData();
                formData.append("file", blob);
                formData.append('user', JSON.stringify(vkflowers.user));
                formData.append('order', $scope.textOrder());
                formData.append('price', $scope.getPrice());
                xhr.send(formData);
                xhr.onload = function(){
                    //var res = JSON.parse(this.responseText);
                    //console.log('res',this.responseText);
                    alert('Заказ оформлен!');
                }
            })
        });
    }
}
 
vkflowers = new function(){
    
    this.user = {id: 118151775, first_name: "Виктор", last_name: "Захаров"};
    
} 

$(function () {
    VK.init(function () {
        //VK.callMethod("showSettingsBox", 8214);
        /*VK.api("wall.post", {"message": "Hello!"}, function (data) {
            alert("Post ID:" + data.response.post_id);
        });*/
        console.log('vk init');
        
        VK.api("users.get", {}, function (data) {
            //console.log('data',data);
            vkflowers.user = data.response[0];
        });
        
    }, function () {
        console.log('error init'); 
    }, '5.65');
})


