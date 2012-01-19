public class Item {
	double[] Vector;
	Object[] RawVector;
	public double Score = 0.0;

	public Item(double[] vec, Object[] raw) {
		Vector = (double[]) vec.clone();
		RawVector = raw.clone();
		/*
		 * for(double i:Vector) { System.out.println(i); }
		 * 
		 * for(Object o:RawVector) { System.out.println(o); }
		 */
	}

	public Item copy() {
		Item new_it = new Item(Vector, RawVector);
		return new_it;
	}
}
