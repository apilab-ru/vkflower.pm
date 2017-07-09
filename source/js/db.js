angular.module('db', ['ngResource']).
    factory('Project', function ($resource) {
        var Project = $resource('/source/db/'
                + ":path.json"
                , {}, {
            save: {
                method: 'PUT'
            }
        }
        );


        Project.listStocks = function (cb) {
            return Project.query({path: 'stocks'});
        }

        Project.getBasket = function () {
            if(('basket' in localStorage) && localStorage.basket && localStorage.basket!= null){
                return JSON.parse(localStorage['basket']);
            }else{
                return Project.query({path: 'basket'});
            }
        }

        Project.saveBasket = function (data) {
            localStorage['basket'] = JSON.stringify(data);
            return Project.save({path: 'basket'},
                    angular.extend({}, this), data);
        }

        return Project;
    });