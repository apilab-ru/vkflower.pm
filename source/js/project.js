angular.module('project', ['db']).
  config(function($routeProvider) {
    $routeProvider.
      when('/', {controller:listStocks, templateUrl:'/templates/list.html'}).
      when('/basket', {controller:basket, templateUrl:'/templates/basket.html'}).
      otherwise({redirectTo:'/'});
  });
  
function listStocks($scope,Project){
    $scope.stocks = Project.listStocks();//query({path : 'stocks'});
}
 