function Basket($scope,Project){
    
    $scope.basket = Project.getBasket();
    $scope.stocks = Project.listStocks();
    
    $scope.count = 0;
    
    $scope.sum = function(){
        var sum  = 0;
        var count = 0;
        angular.forEach($scope.basket, function(stock) {
            sum   += (stock.price * stock.count)
            count += stock.count;
        });
        $scope.count = count;
        return sum;
    }
    
    $scope.checkStock = function(id){
        var stock = _.find($scope.basket, function(it){ return it.id==id });
        if(stock){
            return stock.count;
        }else{
            return 0;
        }
    }
    
    $scope.addStock = function(id){
        var stock = _.find($scope.basket, function(it){ return it.id==id });
        if(stock){
            stock.count ++;
        }else{
            var parent = _.find($scope.stocks, function(it){ return it.id==id });
            $scope.basket.push({
                id    : id,
                name  : parent.name,
                img   : parent.img,
                count : 1,
                price : parent.price
            });
        }
        Project.saveBasket($scope.basket);
    }
    
    $scope.removeStock = function(id,isTable){
        var stock = _.find($scope.basket, function(it){ return it.id==id });
        if(stock.count!= 0){
            stock.count --;
            if(stock.count==0){
                $scope.deleteFilteredItem(stock.$$hashKey,$scope.basket);
            }
            Project.saveBasket($scope.basket);
        }
    }
    
    $scope.deleteFilteredItem = function (hashKey, sourceArray) {
        angular.forEach(sourceArray, function (obj, index) {
            if (obj.$$hashKey === hashKey) {
                sourceArray.splice(index, 1);
                return;
            };
        });
    }
}