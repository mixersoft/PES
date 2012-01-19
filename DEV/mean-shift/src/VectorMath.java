public class VectorMath {
	public static double[] zero(double[] x) {
		return new double[x.length];
	}
	
	public static double[] add(double[] x, double[] y) {
		double[] res = new double[x.length];
		for(int i = 0; i < x.length; i++) {
			res[i] = x[i] + y[i]; 
		}
		return res;
	}

	public static double[] sub(double[] x, double[] y) {
		double[] res = new double[x.length];
		for(int i = 0; i < x.length; i++) {
			res[i] = x[i] - y[i];
		}
		return res;
	}

	public static double[] mul(double[] x, double sc) {
		double[] res = new double[x.length];
		for(int i = 0; i < x.length; i++) {
			res[i] = x[i]*sc;
		}
		return res;
	}

	public static void print(double[] x) {
		System.out.print("[");
		for(int i = 0; i < x.length; i++) {
			System.out.print(x[i]);
			System.out.print(" ");
		}
		System.out.println("]");
	}
}
